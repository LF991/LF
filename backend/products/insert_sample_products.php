<?php
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = getDB();

    // Sample products data
    $products = [
        // Fruits et Légumes
        ['Nom' => 'Pommes Gala', 'Description' => 'Pommes rouges croquantes et juteuses', 'Prix' => 2.50, 'Stock' => 150, 'Categorie' => 'Fruits et Légumes', 'Image_URL' => 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=300', 'Poids' => 1.5],
        ['Nom' => 'Bananes Bio', 'Description' => 'Bananes biologiques mûres à point', 'Prix' => 1.80, 'Stock' => 200, 'Categorie' => 'Fruits et Légumes', 'Image_URL' => 'https://images.unsplash.com/photo-1571771019784-3ff35f4f4277?w=300', 'Poids' => 1.2],
        ['Nom' => 'Oranges Valencia', 'Description' => 'Oranges juteuses d\'Espagne', 'Prix' => 3.20, 'Stock' => 120, 'Categorie' => 'Fruits et Légumes', 'Image_URL' => 'https://images.unsplash.com/photo-1547514701-42782101795e?w=300', 'Poids' => 2.0],
        ['Nom' => 'Tomates Cerises', 'Description' => 'Tomates cerises biologiques', 'Prix' => 4.50, 'Stock' => 80, 'Categorie' => 'Fruits et Légumes', 'Image_URL' => 'https://images.unsplash.com/photo-1546470427-e9b3ba2e4c0b?w=300', 'Poids' => 0.5],
        ['Nom' => 'Avocats Hass', 'Description' => 'Avocats mûrs parfaits pour le guacamole', 'Prix' => 2.80, 'Stock' => 60, 'Categorie' => 'Fruits et Légumes', 'Image_URL' => 'https://images.unsplash.com/photo-1523049673857-eb18f1d7b578?w=300', 'Poids' => 0.3],

        // Produits Laitiers
        ['Nom' => 'Lait Entier', 'Description' => 'Lait frais entier 1L', 'Prix' => 1.20, 'Stock' => 100, 'Categorie' => 'Produits Laitiers', 'Image_URL' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=300', 'Poids' => 1.0],
        ['Nom' => 'Fromage Cheddar', 'Description' => 'Fromage cheddar affiné 200g', 'Prix' => 5.90, 'Stock' => 45, 'Categorie' => 'Produits Laitiers', 'Image_URL' => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?w=300', 'Poids' => 0.2],
        ['Nom' => 'Yaourt Nature', 'Description' => 'Yaourt nature bio 500g', 'Prix' => 2.30, 'Stock' => 75, 'Categorie' => 'Produits Laitiers', 'Image_URL' => 'https://images.unsplash.com/photo-1488477304112-4944851de03d?w=300', 'Poids' => 0.5],
        ['Nom' => 'Beurre Doux', 'Description' => 'Beurre doux 250g', 'Prix' => 3.40, 'Stock' => 55, 'Categorie' => 'Produits Laitiers', 'Image_URL' => 'https://images.unsplash.com/photo-1589985270826-4b7bb135bc9d?w=300', 'Poids' => 0.25],
        ['Nom' => 'Crème Fraîche', 'Description' => 'Crème fraîche épaisse 200ml', 'Prix' => 2.10, 'Stock' => 40, 'Categorie' => 'Produits Laitiers', 'Image_URL' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=300', 'Poids' => 0.2],

        // Viandes et Poissons
        ['Nom' => 'Filet de Saumon', 'Description' => 'Filet de saumon frais norvégien', 'Prix' => 15.90, 'Stock' => 25, 'Categorie' => 'Viandes et Poissons', 'Image_URL' => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=300', 'Poids' => 0.4],
        ['Nom' => 'Escalopes de Poulet', 'Description' => 'Escalopes de poulet fermier 500g', 'Prix' => 8.50, 'Stock' => 35, 'Categorie' => 'Viandes et Poissons', 'Image_URL' => 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=300', 'Poids' => 0.5],
        ['Nom' => 'Bœuf Haché', 'Description' => 'Bœuf haché 5% MG 500g', 'Prix' => 7.20, 'Stock' => 30, 'Categorie' => 'Viandes et Poissons', 'Image_URL' => 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=300', 'Poids' => 0.5],
        ['Nom' => 'Thon Rouge', 'Description' => 'Thon rouge frais du golfe de Gascogne', 'Prix' => 22.50, 'Stock' => 15, 'Categorie' => 'Viandes et Poissons', 'Image_URL' => 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=300', 'Poids' => 0.6],
        ['Nom' => 'Saucisses de Toulouse', 'Description' => 'Saucisses pur porc 6 pièces', 'Prix' => 6.80, 'Stock' => 50, 'Categorie' => 'Viandes et Poissons', 'Image_URL' => 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=300', 'Poids' => 0.8],

        // Épicerie
        ['Nom' => 'Riz Basmati', 'Description' => 'Riz basmati extra long 1kg', 'Prix' => 3.90, 'Stock' => 80, 'Categorie' => 'Épicerie', 'Image_URL' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=300', 'Poids' => 1.0],
        ['Nom' => 'Pâtes Spaghetti', 'Description' => 'Spaghetti italiens 500g', 'Prix' => 1.50, 'Stock' => 120, 'Categorie' => 'Épicerie', 'Image_URL' => 'https://images.unsplash.com/photo-1621996346565-e3dbc353d2e5?w=300', 'Poids' => 0.5],
        ['Nom' => 'Huile d\'Olive', 'Description' => 'Huile d\'olive extra vierge 75cl', 'Prix' => 8.90, 'Stock' => 65, 'Categorie' => 'Épicerie', 'Image_URL' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=300', 'Poids' => 0.75],
        ['Nom' => 'Café Arabica', 'Description' => 'Café en grains arabica 250g', 'Prix' => 6.50, 'Stock' => 40, 'Categorie' => 'Épicerie', 'Image_URL' => 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=300', 'Poids' => 0.25],
        ['Nom' => 'Chocolat Noir', 'Description' => 'Chocolat noir 70% cacao 100g', 'Prix' => 3.20, 'Stock' => 90, 'Categorie' => 'Épicerie', 'Image_URL' => 'https://images.unsplash.com/photo-1606312619070-d48b4c652a52?w=300', 'Poids' => 0.1]
    ];

    // Insert products
    $inserted = 0;
    foreach ($products as $product) {
        try {
            // Determine status based on stock: stock > 0 = Disponible, stock <= 0 = Indisponible
            $statut = $product['Stock'] > 0 ? 'Disponible' : 'Indisponible';
            
            $stmt = $pdo->prepare("INSERT INTO produit (Nom, Description, Prix, Stock, Categorie, Image_URL, Poids, Statut, Date_Ajout)
                                  VALUES (:nom, :description, :prix, :stock, :categorie, :image_url, :poids, :statut, NOW())");

            $stmt->execute([
                ':nom' => $product['Nom'],
                ':description' => $product['Description'],
                ':prix' => $product['Prix'],
                ':stock' => $product['Stock'],
                ':categorie' => $product['Categorie'],
                ':image_url' => $product['Image_URL'],
                ':poids' => $product['Poids'],
                ':statut' => $statut
            ]);

            $inserted++;
        } catch (Exception $e) {
            // Continue with next product if one fails
            continue;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "$inserted produits insérés avec succès dans la base de données",
        'total_attempted' => count($products),
        'total_inserted' => $inserted
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de l\'insertion des produits: ' . $e->getMessage()
    ]);
}
?>
