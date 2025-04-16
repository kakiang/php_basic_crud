<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$dbHost = 'localhost';
$dbName = 'basic_crud_db';
$dbUser = 'user'; 
$dbPass = 'pass'; 
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$message = '';
$errorMessage = '';
$action = $_GET['action'] ?? 'list';
$id_utilisateur = null;
$user_data = ['id' => '', 'nom' => '', 'prenoms' => '', 'email' => '']; // Données pour le formulaire

$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=$charset";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// === TRAITEMENT DES ACTIONS (Delete, Add, Update via POST) ===

if ($action == 'delete' && isset($_GET['id'])) {
    $id_utilisateur_a_supprimer = $_GET['id'];

    if (!filter_var($id_utilisateur_a_supprimer, FILTER_VALIDATE_INT)) {
        header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']) . "?status=invalid_id");
        exit;
    } else {
        try {
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id_utilisateur_a_supprimer, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']) . "?status=deleted");
            } else {
                header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']) . "?status=not_found");
            }
            exit;

        } catch (PDOException $e) {
            // En cas d'erreur lors de la suppression, rediriger avec un message d'erreur
             $error_code = urlencode($e->getCode());
             header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']) . "?status=delete_error&code=" . $error_code);
             exit;
        }
    }
}

// --- Traitement des SOUMISSIONS POST (Ajout ou Modification) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = trim($_POST['nom'] ?? '');
    $prenoms = trim($_POST['prenoms'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $id_utilisateur = $_POST['id'] ?? null;

    // Validation
    if (empty($nom) || empty($prenoms) || empty($email)) {
        $errorMessage = "Tous les champs (Nom, Prénom(s), Email) sont obligatoires.";
        $user_data = ['id' => $id_utilisateur, 'nom' => $nom, 'prenoms' => $prenoms, 'email' => $email];
        $action = $id_utilisateur ? 'edit' : 'add';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Format d'email invalide.";
        $user_data = ['id' => $id_utilisateur, 'nom' => $nom, 'prenoms' => $prenoms, 'email' => $email];
        $action = $id_utilisateur ? 'edit' : 'add';
    } else {
        try {
            if ($id_utilisateur) {
                // UPDATE
                 $sqlCheckEmail = "SELECT id FROM users WHERE email = :email AND id != :id";
                 $stmtCheck = $pdo->prepare($sqlCheckEmail);
                 $stmtCheck->bindParam(':email', $email);
                 $stmtCheck->bindParam(':id', $id_utilisateur, PDO::PARAM_INT);
                 $stmtCheck->execute();
                 if ($stmtCheck->fetch()) {
                    throw new PDOException("Cet email est déjà utilisé par un autre utilisateur.", 23000);
                 }
                 $sql = "UPDATE users SET nom = :nom, prenoms = :prenoms, email = :email WHERE id = :id";
                 $stmt = $pdo->prepare($sql);
                 $stmt->bindParam(':id', $id_utilisateur, PDO::PARAM_INT);
            } else {
                // INSERT
                 $sqlCheckEmail = "SELECT id FROM users WHERE email = :email";
                 $stmtCheck = $pdo->prepare($sqlCheckEmail);
                 $stmtCheck->bindParam(':email', $email);
                 $stmtCheck->execute();
                 if ($stmtCheck->fetch()) {
                     throw new PDOException("Cet email existe déjà.", 23000);
                 }
                 $sql = "INSERT INTO users (nom, prenoms, email) VALUES (:nom, :prenoms, :email)";
                 $stmt = $pdo->prepare($sql);
            }

            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenoms', $prenoms);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            $status_msg = $id_utilisateur ? 'updated' : 'added';
            header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']) . "?status=" . $status_msg);
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errorMessage = $e->getMessage();
            } else {
                $errorMessage = "Erreur base de données : " . $e->getMessage();
            }
            $user_data = ['id' => $id_utilisateur, 'nom' => $nom, 'prenoms' => $prenoms, 'email' => $email];
            $action = $id_utilisateur ? 'edit' : 'add'; // Force l'affichage du formulaire correct
        }
    }
}

// === PRÉPARATION DES DONNÉES POUR L'AFFICHAGE ===

if ($action == 'edit') {
    $id_utilisateur_edit = $_GET['id'] ?? null; // Renommé pour éviter conflit avec $id_utilisateur de POST
    if (!$id_utilisateur_edit || !filter_var($id_utilisateur_edit, FILTER_VALIDATE_INT)) {
        $errorMessage = "ID utilisateur invalide pour la modification.";
        $action = 'list';
    } else {
        try {
            $sql = "SELECT id, nom, prenoms, email FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id_utilisateur_edit, PDO::PARAM_INT);
            $stmt->execute();
            $user_data = $stmt->fetch();
            if (!$user_data) {
                $errorMessage = "Utilisateur non trouvé pour la modification.";
                $action = 'list';
            }
        } catch (PDOException $e) {
            $errorMessage = "Erreur lors de la récupération des données utilisateur: " . $e->getMessage();
            $action = 'list';
        }
    }
}

$users = [];
if ($action == 'list') {
     // Vérifier les messages de statut après redirection
    if (isset($_GET['status'])) {
        switch ($_GET['status']) {
            case 'added':
                $message = "Utilisateur ajouté avec succès !";
                break;
            case 'updated':
                $message = "Utilisateur modifié avec succès !";
                break;
            case 'deleted':
                $message = "Utilisateur supprimé avec succès !"; // Ajout du message de suppression
                break;
            case 'invalid_id':
                $errorMessage = "L'opération a échoué car l'ID fourni était invalide.";
                break;
            case 'not_found':
                 $errorMessage = "L'opération a échoué car l'utilisateur n'a pas été trouvé.";
                 break;
            case 'delete_error':
                 $error_code = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : 'inconnu';
                 $errorMessage = "Erreur lors de la suppression de l'utilisateur (Code: $error_code). Vérifiez les dépendances éventuelles.";
                 break;
        }
    }

    try {
        $sql = "SELECT id, nom, prenoms, email FROM users ORDER BY nom, prenoms";
        $stmt = $pdo->query($sql);
        $users = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errorMessage = "Erreur lors de la récupération de la liste des utilisateurs: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { padding-top: 20px; }
        .container { max-width: 800px; }
        .action-buttons a, .action-buttons button { margin-right: 5px; margin-bottom: 5px; } /* Style pour boutons/liens */
    </style>
</head>
<body>
    <div class="container">

        <h1 class="mb-4 text-center">Gestion des Utilisateurs</h1>

        <?php // Affichage des messages succès ou erreurs ?>
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($errorMessage) && ($action == 'list')): // Afficher les erreurs sur la liste ?>
             <div class="alert alert-danger alert-dismissible fade show" role="alert">
                 <?php echo htmlspecialchars($errorMessage); ?>
                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             </div>
        <?php endif; ?>

        <?php if ($action == 'add' || $action == 'edit'): ?>
            <?php // --- Formulaire d'Ajout ou de Modification --- ?>
            <h2><?php echo ($action == 'edit' ? 'Modifier' : 'Ajouter'); ?> un utilisateur</h2>

            <?php if (!empty($errorMessage)): // Afficher les erreurs spécifiques au formulaire ?>
                 <div class="alert alert-danger" role="alert">
                     <?php echo htmlspecialchars($errorMessage); ?>
                 </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_data['id'] ?? ''); ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="nom" class="form-label">Nom :</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user_data['nom'] ?? ''); ?>" required>
                    <div class="invalid-feedback">Veuillez fournir un nom.</div>
                </div>
                <div class="mb-3">
                    <label for="prenoms" class="form-label">Prénom(s) :</label>
                    <input type="text" class="form-control" id="prenoms" name="prenoms" value="<?php echo htmlspecialchars($user_data['prenoms'] ?? ''); ?>" required>
                    <div class="invalid-feedback">Veuillez fournir au moins un prénom.</div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email :</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                    <div class="invalid-feedback">Veuillez fournir un email valide.</div>
                </div>
                <div class="d-flex justify-content-between">
                     <button type="submit" class="btn btn-primary"><?php echo ($action == 'edit' ? 'Enregistrer les modifications' : 'Ajouter l\'utilisateur'); ?></button>
                     <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </form>

        <?php else: ?>
            <?php // --- Affichage de la Liste des Utilisateurs --- ?>
            <div class="d-flex justify-content-end mb-3">
                 <a href="?action=add" class="btn btn-success">Ajouter un nouvel utilisateur</a>
            </div>

            <?php if (!empty($users)): ?>
                <table class="table table-striped table-hover table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prénom(s)</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['nom']); ?></td>
                            <td><?php echo htmlspecialchars($user['prenoms']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="action-buttons">
                                <a href="?action=edit&id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-sm btn-warning" title="Modifier">Modifier</a>
                                <a href="?action=delete&id=<?php echo htmlspecialchars($user['id']); ?>"
                                   class="btn btn-sm btn-danger"
                                   title="Supprimer"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer définitivement l\'utilisateur \'<?php echo htmlspecialchars(addslashes($user['prenoms'] . ' ' . $user['nom'])); ?>\' ?');">
                                   Supprimer
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                 <?php if(empty($errorMessage)) : ?>
                    <p class="text-center">Aucun utilisateur enregistré pour le moment.</p>
                 <?php endif; ?>
            <?php endif; ?>

        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
      (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
          .forEach(function (form) {
            form.addEventListener('submit', function (event) {
              if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
              }
              form.classList.add('was-validated')
            }, false)
          })
      })()
    </script>
</body>
</html>
