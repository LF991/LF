-- Add image URLs to existing products
UPDATE produit SET Image_URL = 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=400&h=300&fit=crop' WHERE ID_Produit = 1; -- Lait
UPDATE produit SET Image_URL = 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400&h=300&fit=crop' WHERE ID_Produit = 2; -- Pain
UPDATE produit SET Image_URL = 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=400&h=300&fit=crop' WHERE ID_Produit = 3; -- Pommes
UPDATE produit SET Image_URL = 'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=400&h=300&fit=crop' WHERE ID_Produit = 4; -- Poulet
UPDATE produit SET Image_URL = 'https://images.unsplash.com/photo-1553787499-6f9133860278?w=400&h=300&fit=crop' WHERE ID_Produit = 5; -- Eau minérale

-- Add more sample products with images
INSERT INTO produit (Nom, Description, Prix, Stock, Categorie, Image_URL, Statut, Poids) VALUES
('Bananes Bio', 'Bananes bio équitables, 1kg', 2.80, 60, 'Fruits', 'https://images.unsplash.com/photo-1571771019784-3ff35f4f4277?w=400&h=300&fit=crop', 'Disponible', 1.00),
('Fromage Comté', 'Fromage comté AOP, 200g', 8.50, 25, 'Produits laitiers', 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?w=400&h=300&fit=crop', 'Disponible', 0.20),
('Tomates Cerises', 'Tomates cerises bio, 500g', 3.90, 45, 'Légumes', 'https://images.unsplash.com/photo-1546470427-e9e826f4b5e5?w=400&h=300&fit=crop', 'Disponible', 0.50),
('Saumon Frais', 'Filet de saumon frais, 400g', 15.90, 20, 'Poissons', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=400&h=300&fit=crop', 'Disponible', 0.40),
('Pâtes Complètes', 'Pâtes complètes bio, 500g', 2.20, 80, 'Épicerie', 'https://images.unsplash.com/photo-1551467847-0d94f8c5562c?w=400&h=300&fit=crop', 'Disponible', 0.50),
('Huile d\'Olive', 'Huile d\'olive extra vierge, 75cl', 9.90, 35, 'Épicerie', 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400&h=300&fit=crop', 'Disponible', 0.75),
('Café Arabica', 'Café en grains arabica, 250g', 6.50, 40, 'Boissons', 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=400&h=300&fit=crop', 'Disponible', 0.25),
('Chocolat Noir', 'Chocolat noir 70%, 100g', 3.20, 55, 'Épicerie', 'https://images.unsplash.com/photo-1606312619070-d48b4c652a52?w=400&h=300&fit=crop', 'Disponible', 0.10),
('Carottes Bio', 'Carottes bio, 1kg', 2.10, 70, 'Légumes', 'https://images.unsplash.com/photo-1582515073490-39981397c445?w=400&h=300&fit=crop', 'Disponible', 1.00),
('Jus d\'Orange', 'Jus d\'orange pressé, 1L', 4.50, 50, 'Boissons', 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?w=400&h=300&fit=crop', 'Disponible', 1.00),
('Riz Basmati', 'Riz basmati bio, 1kg', 3.80, 65, 'Épicerie', 'https://images.unsplash.com/photo-1536304993881-ff6e9eefa2a6?w=400&h=300&fit=crop', 'Disponible', 1.00),
('Beurre Salé', 'Beurre salé fermier, 250g', 4.20, 30, 'Produits laitiers', 'https://images.unsplash.com/photo-1589985270826-4b7bb135bc9d?w=400&h=300&fit=crop', 'Disponible', 0.25),
('Pomme de Terre', 'Pommes de terre bio, 2kg', 3.50, 90, 'Légumes', 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=400&h=300&fit=crop', 'Disponible', 2.00),
('Thé Vert', 'Thé vert matcha, 50g', 12.90, 15, 'Boissons', 'https://images.unsplash.com/photo-1544787219-7f47ccb76574?w=400&h=300&fit=crop', 'Disponible', 0.05),
('Quinoa', 'Quinoa bio, 500g', 5.90, 40, 'Épicerie', 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=300&fit=crop', 'Disponible', 0.50);
