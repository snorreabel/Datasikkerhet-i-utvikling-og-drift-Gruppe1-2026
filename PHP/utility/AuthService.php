<?php
/**
 * AuthService - Autentiseringsservice
 * 
 * Håndterer all autentiseringslogikk for systemet.
 * Steg 1: Ingen sikkerhetstiltak implementert (bevisst).
 */

class AuthService {
    
    /**
     * Logger inn en bruker basert på epost, passord og rolle
     * 
     * @param string $epost Brukerens e-postadresse
     * @param string $passord Brukerens passord
     * @param string $rolle Brukerens rolle (student, foreleser, admin)
     * @return array Returnerer ['success' => bool, 'message' => string, 'user' => array|null]
     */
    public static function login($epost, $passord, $rolle) {
        require_once __DIR__ . '/db.php';
        $pdo = getDB();
        
        if (empty($epost) || empty($passord)) {
            return [
                'success' => false,
                'message' => 'Vennligst fyll ut alle felt',
                'user' => null
            ];
        }
        
        // Hent bruker basert på epost og rolle
        $stmt = $pdo->prepare("SELECT * FROM bruker WHERE epost = ? AND rolle = ?");
        $stmt->execute([$epost, $rolle]);
        $bruker = $stmt->fetch();
        
        if ($bruker) {
            // Verifiser passord med password_verify()
            if (password_verify($passord, $bruker['passord'])) {
                // Sett sesjonsvariable
                $_SESSION['user_id'] = $bruker['user_id'];
                $_SESSION['navn'] = $bruker['navn'];
                $_SESSION['epost'] = $bruker['epost'];
                $_SESSION['rolle'] = $bruker['rolle'];
                $_SESSION['innlogget'] = true;
                
                return [
                    'success' => true,
                    'message' => 'Innlogging vellykket',
                    'user' => $bruker
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Ugyldig e-post, passord eller rolle',
            'user' => null
        ];
    }
    
    /**
     * Registrerer en ny bruker
     *
     * @param string $navn Brukerens navn
     * @param string $epost Brukerens e-postadresse
     * @param string $passord Brukerens passord
     * @param string $rolle Brukerens rolle (student, foreleser, admin)
     * @return array Returnerer ['success' => bool, 'message' => string]
     */
    public static function register($navn, $epost, $passord, $rolle) {
        require_once __DIR__ . '/db.php';
        $pdo = getDB();

        // Valider input
        if (empty($navn) || empty($epost) || empty($passord)) {
            return [
                'success' => false,
                'message' => 'Vennligst fyll ut alle felt'
            ];
        }

        // Sjekk om bruker allerede eksisterer
        $stmt_sjekk = $pdo->prepare("SELECT * FROM bruker WHERE epost = ?");
        $stmt_sjekk->execute([$epost]);

        if ($stmt_sjekk->fetch()) {
            return [
                'success' => false,
                'message' => 'En bruker med denne e-posten eksisterer allerede'
            ];
        }

        // Opprett bruker
        $stmt_insert = $pdo->prepare("INSERT INTO bruker (navn, epost, passord, rolle) VALUES (?, ?, ?, ?)");
        $success = $stmt_insert->execute([
            $navn,
            $epost,
            $passord,
            $rolle
        ]);

        if ($success) {
            return [
                'success' => true,
                'message' => 'Bruker opprettet. Du kan nå logge inn'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Kunne ikke opprette bruker. Prøv igjen'
            ];
        }
    }
    
    /**
     * Logger ut brukeren ved å fjerne alle sesjonsvariable
     */
    public static function logout() {
        session_start();
        session_unset();
        session_destroy();
    }
    
    /**
     * Sjekker om brukeren er innlogget
     * 
     * @return bool True hvis innlogget, false ellers
     */
    public static function isLoggedIn() {
        return isset($_SESSION['innlogget']) && $_SESSION['innlogget'] === true;
    }
    
    /**
     * Sjekker om brukeren har en bestemt rolle
     * 
     * @param string $rolle Rolle å sjekke mot
     * @return bool True hvis bruker har rolle, false ellers
     */
    public static function hasRole($rolle) {
        return self::isLoggedIn() && isset($_SESSION['rolle']) && $_SESSION['rolle'] === $rolle;
    }
    
    /**
     * Krever at brukeren er innlogget, ellers redirect til login
     * 
     * @param string|null $rolle Spesifikk rolle som kreves (valgfri)
     * @param string $redirectUrl URL å redirecte til hvis ikke innlogget
     */
    public static function requireLogin($rolle = null, $redirectUrl = '../index.php') {
        if (!self::isLoggedIn()) {
            header("Location: $redirectUrl");
            exit;
        }
        
        if ($rolle !== null && !self::hasRole($rolle)) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Henter innlogget brukerens data
     * 
     * @return array|null Brukerdata hvis innlogget, null ellers
     */
    public static function getUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'navn' => $_SESSION['navn'] ?? null,
            'epost' => $_SESSION['epost'] ?? null,
            'rolle' => $_SESSION['rolle'] ?? null
        ];
    }
}
?>
