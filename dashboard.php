<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Supermarché Online</title>
    <link rel="stylesheet" href="frontend/css/style.css">
</head>
<body>
    <header class="navbar">
        <div class="container">
            <h1>Supermarché Online</h1>
            <nav>
                <a href="dashboard.php">Accueil</a>
                <a href="frontend/client/products.html">Produits</a>
                <a href="frontend/client/cart.html">Panier</a>
                <a href="logout.php">Déconnexion</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="hero">
            <h2>Bienvenue, <?php echo htmlspecialchars($_SESSION['user_email']); ?>!</h2>
            <p>Vous êtes maintenant connecté à votre compte Supermarché Online.</p>
            <a href="frontend/client/products.html" class="btn btn-primary">Voir les produits</a>
        </div>
    </main>
</body>
</html>
