/**
 * CastleMarket AI Assistant - FAQ Chatbot
 * Un assistant virtuel qui répond aux questions fréquentes des clients
 */

class AIChatbot {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.faqData = this.initializeFAQ();
        this.init();
    }

    init() {
        // Create chatbot HTML elements
        this.createChatbotElements();
        // Setup event listeners
        this.setupEventListeners();
        // Load chat history from localStorage
        this.loadChatHistory();
    }

    initializeFAQ() {
        return {
            // Produits et Catalogue
            'produit': {
                keywords: ['produit', 'produits', 'catalogue', 'article', 'articles', 'acheter', 'prix', 'coût'],
                response: `📦 **Produits & Catalogue**

Voici comment je peux vous aider avec nos produits :

• **Parcourir les produits** : Cliquez sur "Produits" dans le menu pour voir tout notre catalogue
• **Rechercher** : Utilisez la barre de recherche pour trouver un produit spécifique
• **Filtrer** : Vous pouvez filtrer par catégorie, prix et disponibilité
• **Détails** : Cliquez sur un produit pour voir tous les détails (description, prix, stock)

N'hésitez pas à me poser une question plus précise !`,
                category: 'products'
            },
            'stock': {
                keywords: ['stock', 'disponible', 'rupture', 'quantité', 'disponibilité'],
                response: `📊 **Disponibilité des Produits**

• Chaque produit affiche son état de stock
• **En stock** : Produit disponible en quantité suffisante
• **Stock faible** : Il reste quelques unités, dépêchez-vous !
• **Rupture de stock** : Produit temporairement indisponible

Vous pouvez vérifier la disponibilité directement sur la page produit.`,
                category: 'products'
            },
            'categorie': {
                keywords: ['catégorie', 'categorie', 'type', 'rayon', 'frais', 'épicerie', 'boisson'],
                response: `🏷️ **Catégories de Produits**

Notre catalogue contient plusieurs catégories :
• **Fruits & Légumes** - Produits frais
• **Viandes & Poissons** - Produits frais également
• **Produits laitiers** - Lait, fromage, yaourts
• **Épicerie** - Conserves, pâtes, riz, sauces
• **Boissons** - Eaux, jus, sodas
• **Snacks & Desserts**

Utilisez les filtres sur la page produits pour naviguer par catégorie !`,
                category: 'products'
            },

            // Commandes
            'commande': {
                keywords: ['commande', 'commander', 'order', 'acheter', 'passer'],
                response: `🛒 **Passer une Commande**

Voici les étapes pour commander :

1. **Parcourez** les produits sur la page "Produits"
2. **Ajoutez** les produits souhaité au panier
3. **Vérifiez** votre panier
4. **Validez** la commande
5. **Attendez** la confirmation

Vous pouvez suivre vos commandes depuis la page "Commandes" dans votre espace client.`,
                category: 'orders'
            },
            'suivi': {
                keywords: ['suivi', 'status', 'état', 'avancement', 'livré', 'préparation'],
                response: `📍 **Suivi de Commande**

Suivez l'état de votre commande :
• **En attente** - Commande reçue, en cours de traitement
• **Confirmée** - Commande validée
• **En préparation** - Nous préparons votre commande
• **Prête** - Commande prête, en attente du livreur
• **En livraison** - Votre commande est en route
• **Livrée** - Commande livrée !

 Consultez votre page "Commandes" pour voir le détail.`,
                category: 'orders'
            },
            'annuler': {
                keywords: ['annuler', 'annulation', 'annulé', 'supprimer'],
                response: `❌ **Annuler une Commande**

Pour annuler une commande :
• Rendez-vous sur la page "Commandes"
• Cliquez sur la commande concernée
• Utilisez le bouton "Annuler" si disponible

⚠️ **Attention** : Vous ne pouvez annuler qu'une commande qui n'est pas encore en livraison.`,
                category: 'orders'
            },

            // Livraison
            'livraison': {
                keywords: ['livraison', 'livreur', 'livrer', 'adresse', 'horaire', 'heure'],
                response: `🚚 **Livraison**

• **Zone de livraison** : Nous livrons dans toute la ville
• **Délai** : Habituellement 24-48h après confirmation
• **Frais** : Livraison gratuite à partir de 50€ d'achat
• **Suivi** : Vous pouvez suivre votre livreur en temps réel

确保ifiez que votre adresse est correctement enregistrée dans votre profil !`,
                category: 'delivery'
            },
            'adresse': {
                keywords: ['adresse', 'livrer', 'where', 'où', 'changer'],
                response: `📍 **Adresse de Livraison**

Pour modifier votre adresse :
1. Allez dans "Mon Compte"
2. Cliquez sur "Modifier le profil"
3. Mettez à jour votre adresse

Veillez à ce que votre adresse soit complète (rue, ville, code postal) pour éviter les problèmes de livraison.`,
                category: 'delivery'
            },
            'retard': {
                keywords: ['retard', 'retardé', 'en retard', 'delai', 'délai', 'trop long'],
                response: `⏰ **Retard de Livraison**

Si votre commande est en retard :

1. Vérifiez le statut sur la page "Commandes"
2. Contactez-nous si le délai dépasse 48h

Nous faisons de notre mieux pour livrer dans les temps ! Le livreur peut être confronté à des conditions de circulation difficiles.`,
                category: 'delivery'
            },

            // Compte & Paiement
            'compte': {
                keywords: ['compte', 'profil', 'identifiant', 'mot de passe', 'connexion', 'inscription'],
                response: `👤 **Mon Compte**

Gérez votre compte facilement :
• **Voir le profil** : Page "Mon Compte"
• **Modifier les infos** : Cliquez sur "Modifier le profil"
• **Changer le mot de passe** : Dans les paramètres du profil
• **Historique** : Toutes vos commandes sont visibles

Vos données sont sécurisées et resteront confidentielles.`,
                category: 'account'
            },
            'paiement': {
                keywords: ['paiement', 'payer', 'argent', 'carte', 'espèce', 'virement', 'CB'],
                response: `💳 **Paiement**

• **Modes de paiement acceptés** :
  - Carte bancaire (CB, Visa, Mastercard)
  - Espèces à la livraison
  - Bon d'achat

• **Sécurité** : Vos paiements sont sécurisés et cryptés
• **Facture** : Reçue automatiquement par email après commande

Pour toute question sur un paiement, contactez le service client.`,
                category: 'account'
            },
            'inscription': {
                keywords: ['inscription', 'inscrire', 'créer compte', 's\'enregistrer', 'register'],
                response: `📝 **Créer un Compte**

Pour créer un compte :
1. Cliquez sur "Inscription" en haut de la page
2. Remplissez le formulaire (nom, email, mot de passe)
3. Validez votre inscription
4. Commencez à commander !

**Avantages** :
• Suivi de vos commandes
• Historique d'achats
• Réservation de produits
• Notifications personnalisées`,
                category: 'account'
            },
            'mdp': {
                keywords: ['mdp', 'mot de passe', 'oublié', 'forgotten', 'password'],
                response: `🔑 **Mot de Passe Oublié**

Si vous avez oublié votre mot de passe :
1. Allez sur la page de connexion
2. Cliquez sur "Mot de passe oublié"
3. Entrez votre email
4. Vous recevrez un lien pour réinitialiser

**Conseil** : Choisissez un mot de passe robuste avec au moins 6 caractères.`,
                category: 'account'
            },
            'contact': {
                keywords: ['contact', 'contacter', 'service client', 'aide', 'support', 'chat'],
                response: `📞 **Nous Contacter**

Plusieurs moyens de nous joindre :

• **Ce chatbot** : Disponible 24h/24 pour vos questions fréquentes
• **Email** : support@castlemarket.com
• **Téléphone** : Du lundi au samedi, 9h-18h

Nous répondons généralement sous 24h !`,
                category: 'general'
            },
            'horaire': {
                keywords: ['horaire', 'heures', 'ouverture', 'quand', 'disponible'],
                response: `🕐 **Horaires**

• **Site web** : Disponible 24h/24, 7j/7
• **Commandes** : Possible à tout moment
• **Livraison** : Selon les disponibilités du livreur
• **Service client** : Lun-Sam, 9h-18h

Profitez de notre service à tout moment !`,
                category: 'general'
            },
            'retour': {
                keywords: ['retour', 'rembourser', 'remboursement', 'échange', 'retourner'],
                response: `🔄 **Retours & Remboursements**

• **Politique** : Vous pouvez retourner un produit dans les 7 jours
• **Condition** : Produit non ouvert et dans son emballage d'origine
• **Procédure** : Contactez le service client

Nous remboursons sous 5-10 jours ouvrés après réception du retour.`,
                category: 'general'
            },
            'merci': {
                keywords: ['merci', 'thanks', 'thank you', 'bravo', 'super', 'génial'],
                response: `😊 **De rien !**

C'est un plaisir de vous aider !

N'hésitez pas si vous avez d'autres questions.
Je suis là pour vous assister avec :
• Nos produits
• Vos commandes
• La livraison
• Votre compte

Bonne journée sur CastleMarket ! 🌟`,
                category: 'general'
            },
            'bonjour': {
                keywords: ['bonjour', 'salut', 'hello', 'hi', 'coucou', 'good morning'],
                response: `👋 **Bonjour !**

Bienvenue sur CastleMarket ! Je suis votre assistant virtuel.

Je peux vous aider avec :
• 📦 **Produits** : Catalogue, recherche, disponibilité
• 🛒 **Commandes** : Passer, suivre, annuler
• 🚚 **Livraison** : Adresse, suivi, retards
• 👤 **Compte** : Inscription, paiement, mot de passe

Comment puis-je vous aider aujourd'hui ?`,
                category: 'general'
            },
            'default': {
                keywords: [],
                response: `🤖 **Je suis là pour vous aider !**

Je peux répondre à vos questions sur :

• **Produits** : "Quels fruits avez-vous ?" / "Le produit X est-il disponible ?"
• **Commandes** : "Où est ma commande ?" / "Comment annuler ?"
• **Livraison** : "Combien de temps pour être livré ?" / "Livrez-vous le dimanche ?"
• **Compte** : "Comment m'inscrire ?" / "J'ai oublié mon mot de passe"

Tapez votre question ou un mot-clé pour commencer !`,
                category: 'general'
            }
        };
    }

    createChatbotElements() {
        // Create chatbot container
        const chatbotHTML = `
            <!-- Chatbot Toggle Button -->
            <button id="chatbot-toggle" class="chatbot-toggle-btn" title="Assistant IA - Cliquez pour discuter">
                <i class="fas fa-comments"></i>
                <span class="chatbot-badge">AI</span>
            </button>

            <!-- Chatbot Window -->
            <div id="chatbot-window" class="chatbot-window">
                <div class="chatbot-header">
                    <div class="chatbot-header-info">
                        <div class="chatbot-avatar">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div>
                            <h3>Assistant CastleMarket</h3>
                            <span class="chatbot-status">
                                <span class="status-dot"></span>
                                En ligne
                            </span>
                        </div>
                    </div>
                    <button id="chatbot-close" class="chatbot-close-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div id="chatbot-messages" class="chatbot-messages">
                    <div class="chatbot-message bot">
                        <div class="message-content">
                            <p>👋 <strong>Bonjour !</strong></p>
                            <p>Je suis l'assistant virtuel de CastleMarket.</p>
                            <p>Je peux vous aider avec :</p>
                            <ul>
                                <li>📦 Nos produits et leur disponibilité</li>
                                <li>🛒 Le suivi de vos commandes</li>
                                <li>🚚 La livraison</li>
                                <li>👤 Votre compte client</li>
                            </ul>
                            <p><strong>Posez-moi votre question !</strong></p>
                        </div>
                        <span class="message-time">Maintenant</span>
                    </div>
                </div>

                <div class="chatbot-suggestions">
                    <button class="suggestion-btn" data-question="produit">📦 Produits</button>
                    <button class="suggestion-btn" data-question="commande">🛒 Ma commande</button>
                    <button class="suggestion-btn" data-question="livraison">🚚 Livraison</button>
                    <button class="suggestion-btn" data-question="compte">👤 Mon compte</button>
                </div>

                <div class="chatbot-input">
                    <input type="text" id="chatbot-input" placeholder="Tapez votre question..." autocomplete="off">
                    <button id="chatbot-send" class="chatbot-send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        `;

        // Add CSS styles
        const chatbotStyles = `
            /* Chatbot Styles */
            .chatbot-toggle-btn {
                position: fixed;
                bottom: 24px;
                right: 24px;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, #10b981, #059669);
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 24px;
                z-index: 9999;
                transition: all 0.3s ease;
            }

            .chatbot-toggle-btn:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 20px rgba(16, 185, 129, 0.5);
            }

            .chatbot-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #f59e0b;
                color: white;
                font-size: 10px;
                font-weight: bold;
                padding: 2px 6px;
                border-radius: 10px;
            }

            .chatbot-window {
                position: fixed;
                bottom: 100px;
                right: 24px;
                width: 380px;
                max-width: calc(100vw - 48px);
                height: 500px;
                max-height: calc(100vh - 120px);
                background: white;
                border-radius: 20px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                z-index: 9998;
                display: none;
                flex-direction: column;
                overflow: hidden;
                animation: chatbotSlideUp 0.3s ease;
            }

            .chatbot-window.open {
                display: flex;
            }

            @keyframes chatbotSlideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .chatbot-header {
                background: linear-gradient(135deg, #10b981, #059669);
                padding: 16px 20px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                color: white;
            }

            .chatbot-header-info {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .chatbot-avatar {
                width: 44px;
                height: 44px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
            }

            .chatbot-header h3 {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
            }

            .chatbot-status {
                font-size: 12px;
                display: flex;
                align-items: center;
                gap: 6px;
                opacity: 0.9;
            }

            .status-dot {
                width: 8px;
                height: 8px;
                background: #4ade80;
                border-radius: 50%;
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }

            .chatbot-close-btn {
                background: rgba(255, 255, 255, 0.2);
                border: none;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.2s;
            }

            .chatbot-close-btn:hover {
                background: rgba(255, 255, 255, 0.3);
            }

            .chatbot-messages {
                flex: 1;
                overflow-y: auto;
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 12px;
                background: #f8fafc;
            }

            .chatbot-message {
                display: flex;
                flex-direction: column;
                max-width: 85%;
            }

            .chatbot-message.user {
                align-self: flex-end;
            }

            .chatbot-message.bot {
                align-self: flex-start;
            }

            .chatbot-message .message-content {
                padding: 12px 16px;
                border-radius: 16px;
                font-size: 14px;
                line-height: 1.5;
            }

            .chatbot-message.user .message-content {
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                border-bottom-right-radius: 4px;
            }

            .chatbot-message.bot .message-content {
                background: white;
                color: #374151;
                border: 1px solid #e5e7eb;
                border-bottom-left-radius: 4px;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            }

            .chatbot-message .message-content ul {
                margin: 8px 0;
                padding-left: 20px;
            }

            .chatbot-message .message-content li {
                margin: 4px 0;
            }

            .chatbot-message .message-time {
                font-size: 11px;
                color: #9ca3af;
                margin-top: 4px;
                padding: 0 4px;
            }

            .chatbot-message.user .message-time {
                text-align: right;
            }

            .chatbot-suggestions {
                display: flex;
                gap: 8px;
                padding: 12px 16px;
                background: white;
                border-top: 1px solid #e5e7eb;
                overflow-x: auto;
                flex-shrink: 0;
            }

            .suggestion-btn {
                padding: 8px 12px;
                background: #ecfdf5;
                border: 1px solid #10b981;
                border-radius: 20px;
                font-size: 12px;
                color: #059669;
                cursor: pointer;
                white-space: nowrap;
                transition: all 0.2s;
            }

            .suggestion-btn:hover {
                background: #10b981;
                color: white;
            }

            .chatbot-input {
                display: flex;
                gap: 8px;
                padding: 12px 16px;
                background: white;
                border-top: 1px solid #e5e7eb;
            }

            .chatbot-input input {
                flex: 1;
                padding: 12px 16px;
                border: 2px solid #e5e7eb;
                border-radius: 24px;
                font-size: 14px;
                outline: none;
                transition: border-color 0.2s;
            }

            .chatbot-input input:focus {
                border-color: #10b981;
            }

            .chatbot-send-btn {
                width: 44px;
                height: 44px;
                border: none;
                border-radius: 50%;
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: transform 0.2s, box-shadow 0.2s;
            }

            .chatbot-send-btn:hover {
                transform: scale(1.05);
                box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
            }

            /* Mobile Responsiveness */
            @media (max-width: 480px) {
                .chatbot-toggle-btn {
                    bottom: 16px;
                    right: 16px;
                    width: 54px;
                    height: 54px;
                    font-size: 20px;
                }

                .chatbot-window {
                    bottom: 80px;
                    right: 8px;
                    left: 8px;
                    width: auto;
                    height: calc(100vh - 100px);
                    max-height: none;
                    border-radius: 16px;
                }

                .chatbot-messages {
                    padding: 12px;
                }

                .chatbot-message {
                    max-width: 90%;
                }
            }

            /* Scrollbar Styling */
            .chatbot-messages::-webkit-scrollbar {
                width: 6px;
            }

            .chatbot-messages::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 3px;
            }

            .chatbot-messages::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 3px;
            }

            .chatbot-messages::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }
        `;

        // Create style element
        const styleElement = document.createElement('style');
        styleElement.id = 'chatbot-styles';
        styleElement.textContent = chatbotStyles;

        // Create container element
        const container = document.createElement('div');
        container.id = 'chatbot-container';
        container.innerHTML = chatbotHTML;

        // Add to document
        document.head.appendChild(styleElement);
        document.body.appendChild(container);
    }

    setupEventListeners() {
        // Toggle button
        const toggleBtn = document.getElementById('chatbot-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => this.toggleChat());
        }

        // Close button
        const closeBtn = document.getElementById('chatbot-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeChat());
        }

        // Send button
        const sendBtn = document.getElementById('chatbot-send');
        if (sendBtn) {
            sendBtn.addEventListener('click', () => this.sendMessage());
        }

        // Enter key in input
        const input = document.getElementById('chatbot-input');
        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendMessage();
                }
            });
        }

        // Suggestion buttons
        document.querySelectorAll('.suggestion-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const question = btn.dataset.question;
                this.handleUserInput(question, true);
            });
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            const window = document.getElementById('chatbot-window');
            const toggle = document.getElementById('chatbot-toggle');
            if (this.isOpen && 
                !window.contains(e.target) && 
                !toggle.contains(e.target)) {
                this.closeChat();
            }
        });
    }

    toggleChat() {
        if (this.isOpen) {
            this.closeChat();
        } else {
            this.openChat();
        }
    }

    openChat() {
        const window = document.getElementById('chatbot-window');
        const toggle = document.getElementById('chatbot-toggle');
        
        if (window && toggle) {
            window.classList.add('open');
            toggle.style.display = 'none';
            this.isOpen = true;
            
            // Focus input
            setTimeout(() => {
                const input = document.getElementById('chatbot-input');
                if (input) input.focus();
            }, 100);
            
            // Scroll to bottom
            this.scrollToBottom();
        }
    }

    closeChat() {
        const window = document.getElementById('chatbot-window');
        const toggle = document.getElementById('chatbot-toggle');
        
        if (window && toggle) {
            window.classList.remove('open');
            toggle.style.display = 'flex';
            this.isOpen = false;
        }
    }

    sendMessage() {
        const input = document.getElementById('chatbot-input');
        if (!input) return;

        const message = input.value.trim();
        if (message) {
            this.handleUserInput(message);
            input.value = '';
        }
    }

    handleUserInput(inputText, isSuggestion = false) {
        // Add user message
        const displayText = isSuggestion ? this.getQuestionFromKeyword(inputText) : inputText;
        this.addMessage(displayText, 'user');

        // Find matching response
        const response = this.findResponse(inputText.toLowerCase());

        // Simulate typing delay
        setTimeout(() => {
            this.addMessage(response, 'bot');
        }, 500);
    }

    getQuestionFromKeyword(keyword) {
        const questions = {
            'produit': 'Comment fonctionnent les produits ?',
            'commande': 'Comment suivre ma commande ?',
            'livraison': 'Comment fonctionne la livraison ?',
            'compte': 'Comment gérer mon compte ?'
        };
        return questions[keyword] || keyword;
    }

    findResponse(inputText) {
        // Search through FAQ data
        for (const [key, faq] of Object.entries(this.faqData)) {
            if (key === 'default') continue;
            
            for (const keyword of faq.keywords) {
                if (inputText.includes(keyword)) {
                    this.saveToHistory(inputText, faq.category);
                    return faq.response;
                }
            }
        }

        // Return default response
        return this.faqData.default.response;
    }

    addMessage(text, sender) {
        const messagesContainer = document.getElementById('chatbot-messages');
        if (!messagesContainer) return;

        const time = new Date().toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });

        const messageDiv = document.createElement('div');
        messageDiv.className = `chatbot-message ${sender}`;
        messageDiv.innerHTML = `
            <div class="message-content">
                ${this.formatMessage(text)}
            </div>
            <span class="message-time">${time}</span>
        `;

        messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }

    formatMessage(text) {
        // Convert markdown-like syntax to HTML
        let formatted = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>')
            .replace(/• /g, '<br>• ');

        return `<p>${formatted}</p>`;
    }

    scrollToBottom() {
        const messagesContainer = document.getElementById('chatbot-messages');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    saveToHistory(question, category) {
        const history = JSON.parse(localStorage.getItem('chatbot_history') || '[]');
        history.push({
            question,
            category,
            timestamp: new Date().toISOString()
        });
        
        // Keep only last 50 messages
        if (history.length > 50) {
            history.shift();
        }
        
        localStorage.setItem('chatbot_history', JSON.stringify(history));
    }

    loadChatHistory() {
        const history = JSON.parse(localStorage.getItem('chatbot_history') || '[]');
        // Could implement loading previous messages here
        // For now, we start fresh each session
    }
}

// Initialize chatbot when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.chatbot = new AIChatbot();
});

// Export for global use
window.AIChatbot = AIChatbot;

