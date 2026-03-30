<?php
// src/includes/rfid.php

require_once __DIR__ . '/db.php';

class RFID {

    /** Lit l'UID via le script shell, retourne null si rien */
    public static function readUID(): ?string {
        $raw = shell_exec('sudo ' . RFID_SCRIPT . ' 2>/dev/null');
        if ($raw === null) return null;
        $uid = strtoupper(trim($raw));
        return $uid !== '' ? $uid : null;
    }

    /**
     * Retourne le user + son solde à partir d'un UID de badge.
     * Joint users, badges et comptes.
     */
    public static function findUser(string $uid): ?array {
        $stmt = DB::get()->prepare(
            'SELECT u.id, u.nom, u.prenom, u.role, c.solde
             FROM users u
             JOIN badges b ON b.user_id = u.id
             JOIN comptes c ON c.user_id = u.id
             WHERE b.uid = ? AND b.actif = 1 AND u.actif = 1
             LIMIT 1'
        );
        $stmt->execute([$uid]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Débite le compte d'un user (paiement).
     * Retourne ['ok'=>true] ou ['ok'=>false, 'error'=>'...']
     */
    public static function payer(int $userId, float $montant, int $merchantId): array {
        $db = DB::get();
        try {
            $db->beginTransaction();

            // Verrouillage ligne pour éviter les race conditions
            $row = $db->prepare('SELECT solde FROM comptes WHERE user_id = ? FOR UPDATE');
            $row->execute([$userId]);
            $compte = $row->fetch();

            if (!$compte) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'Compte introuvable.'];
            }

            if ((float)$compte['solde'] < $montant) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'Solde insuffisant.'];
            }

            $nouveauSolde = round((float)$compte['solde'] - $montant, 2);

            $db->prepare('UPDATE comptes SET solde = ? WHERE user_id = ?')
               ->execute([$nouveauSolde, $userId]);

            $db->prepare(
                'INSERT INTO transactions (user_id, merchant_id, montant, type)
                 VALUES (?, ?, ?, "paiement")'
            )->execute([$userId, $merchantId, $montant]);

            $db->commit();
            return ['ok' => true, 'solde' => $nouveauSolde];

        } catch (Exception $e) {
            $db->rollBack();
            return ['ok' => false, 'error' => 'Erreur technique.'];
        }
    }

    /**
     * Crédite le compte d'un user.
     */
    public static function crediter(int $userId, float $montant): array {
        $db = DB::get();
        try {
            $db->beginTransaction();

            $row = $db->prepare('SELECT solde FROM comptes WHERE user_id = ? FOR UPDATE');
            $row->execute([$userId]);
            $compte = $row->fetch();

            if (!$compte) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'Compte introuvable.'];
            }

            $nouveauSolde = round((float)$compte['solde'] + $montant, 2);

            $db->prepare('UPDATE comptes SET solde = ? WHERE user_id = ?')
               ->execute([$nouveauSolde, $userId]);

            $db->prepare(
                'INSERT INTO transactions (user_id, merchant_id, montant, type)
                 VALUES (?, NULL, ?, "credit")'
            )->execute([$userId, $montant]);

            $db->commit();
            return ['ok' => true, 'solde' => $nouveauSolde];

        } catch (Exception $e) {
            $db->rollBack();
            return ['ok' => false, 'error' => 'Erreur technique.'];
        }
    }
}
