#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Application de publication d'articles de blog
Interface graphique moderne avec customtkinter
"""

import customtkinter as ctk
import requests
import json
from tkinter import messagebox
import threading


class BlogPublisherApp(ctk.CTk):
    """Application principale pour publier des articles de blog"""
    
    def __init__(self):
        super().__init__()
        
        # Configuration de la fenêtre
        self.title("Blog Publisher - TIKCAP")
        self.geometry("900x700")
        self.minsize(800, 600)
        
        # Configuration du thème
        ctk.set_appearance_mode("dark")
        ctk.set_default_color_theme("blue")
        
        # Configuration de l'API (modifiable)
        # Pour tester localement, assurez-vous que votre serveur PHP tourne
        # Ex: php -S localhost:8000
        self.api_url = "http://localhost:8000/api/publish.php"
        self.api_key = "TikCap_Secure_API_Key_2026_XyZ"
        
        # Création de l'interface
        self.create_widgets()
        
    def create_widgets(self):
        """Crée tous les widgets de l'interface"""
        
        # Frame principal avec padding
        main_frame = ctk.CTkFrame(self, fg_color="transparent")
        main_frame.pack(fill="both", expand=True, padx=20, pady=20)
        
        # En-tête
        header = ctk.CTkLabel(
            main_frame,
            text="📝 Publier un Article",
            font=ctk.CTkFont(size=28, weight="bold")
        )
        header.pack(pady=(0, 30))
        
        # Frame pour le formulaire avec scrollbar
        form_frame = ctk.CTkScrollableFrame(main_frame, fg_color="transparent")
        form_frame.pack(fill="both", expand=True)
        
        # Champ Titre
        title_label = ctk.CTkLabel(
            form_frame,
            text="Titre de l'article *",
            font=ctk.CTkFont(size=14, weight="bold")
        )
        title_label.pack(anchor="w", pady=(10, 5))
        
        self.title_entry = ctk.CTkEntry(
            form_frame,
            placeholder_text="Entrez le titre de votre article",
            height=40,
            font=ctk.CTkFont(size=13)
        )
        self.title_entry.pack(fill="x", pady=(0, 15))
        
        # Champ Image URL
        image_label = ctk.CTkLabel(
            form_frame,
            text="URL de l'image de couverture *",
            font=ctk.CTkFont(size=14, weight="bold")
        )
        image_label.pack(anchor="w", pady=(10, 5))
        
        self.image_entry = ctk.CTkEntry(
            form_frame,
            placeholder_text="https://exemple.com/image.jpg",
            height=40,
            font=ctk.CTkFont(size=13)
        )
        self.image_entry.pack(fill="x", pady=(0, 15))
        
        # Champ Extrait
        excerpt_label = ctk.CTkLabel(
            form_frame,
            text="Extrait (résumé) *",
            font=ctk.CTkFont(size=14, weight="bold")
        )
        excerpt_label.pack(anchor="w", pady=(10, 5))
        
        self.excerpt_text = ctk.CTkTextbox(
            form_frame,
            height=100,
            font=ctk.CTkFont(size=13)
        )
        self.excerpt_text.pack(fill="x", pady=(0, 15))
        
        # Champ Contenu
        content_label = ctk.CTkLabel(
            form_frame,
            text="Contenu de l'article *",
            font=ctk.CTkFont(size=14, weight="bold")
        )
        content_label.pack(anchor="w", pady=(10, 5))
        
        content_info = ctk.CTkLabel(
            form_frame,
            text="Supporte le HTML basique (ex: <p>, <h2>, <strong>, <em>, <a>, etc.)",
            font=ctk.CTkFont(size=11),
            text_color="gray"
        )
        content_info.pack(anchor="w", pady=(0, 5))
        
        self.content_text = ctk.CTkTextbox(
            form_frame,
            height=250,
            font=ctk.CTkFont(size=13)
        )
        self.content_text.pack(fill="both", expand=True, pady=(0, 20))
        
        # Frame pour les boutons
        button_frame = ctk.CTkFrame(main_frame, fg_color="transparent")
        button_frame.pack(fill="x", pady=(10, 0))
        
        # Bouton Effacer
        clear_button = ctk.CTkButton(
            button_frame,
            text="🗑️ Effacer",
            command=self.clear_form,
            fg_color="gray40",
            hover_color="gray30",
            height=45,
            font=ctk.CTkFont(size=14)
        )
        clear_button.pack(side="left", padx=(0, 10), fill="x", expand=True)
        
        # Bouton Publier
        self.publish_button = ctk.CTkButton(
            button_frame,
            text="🚀 Publier l'Article",
            command=self.publish_article,
            height=45,
            font=ctk.CTkFont(size=14, weight="bold")
        )
        self.publish_button.pack(side="left", fill="x", expand=True)
        
        # Barre de statut
        self.status_label = ctk.CTkLabel(
            main_frame,
            text="Prêt à publier",
            font=ctk.CTkFont(size=12),
            text_color="gray"
        )
        self.status_label.pack(pady=(10, 0))
        
    def validate_form(self):
        """Valide les champs du formulaire"""
        title = self.title_entry.get().strip()
        image_url = self.image_entry.get().strip()
        excerpt = self.excerpt_text.get("1.0", "end-1c").strip()
        content = self.content_text.get("1.0", "end-1c").strip()
        
        if not title:
            messagebox.showerror("Erreur", "Le titre est obligatoire!")
            return False
            
        if not image_url:
            messagebox.showerror("Erreur", "L'URL de l'image est obligatoire!")
            return False
            
        if not excerpt:
            messagebox.showerror("Erreur", "L'extrait est obligatoire!")
            return False
            
        if not content:
            messagebox.showerror("Erreur", "Le contenu est obligatoire!")
            return False
            
        return True
    
    def clear_form(self):
        """Efface tous les champs du formulaire"""
        if messagebox.askyesno("Confirmation", "Voulez-vous vraiment effacer tous les champs ?"):
            self.title_entry.delete(0, "end")
            self.image_entry.delete(0, "end")
            self.excerpt_text.delete("1.0", "end")
            self.content_text.delete("1.0", "end")
            self.update_status("Formulaire effacé", "gray")
    
    def update_status(self, message, color="gray"):
        """Met à jour la barre de statut"""
        self.status_label.configure(text=message, text_color=color)
        self.update_idletasks()
    
    def publish_article(self):
        """Publie l'article via l'API"""
        # Validation
        if not self.validate_form():
            return
        
        # Désactiver le bouton pendant l'envoi
        self.publish_button.configure(state="disabled", text="Publication en cours...")
        self.update_status("Envoi de l'article...", "orange")
        
        # Exécuter la requête dans un thread séparé pour ne pas bloquer l'UI
        thread = threading.Thread(target=self._send_request)
        thread.daemon = True
        thread.start()
    
    def _send_request(self):
        """Envoie la requête HTTP (exécuté dans un thread séparé)"""
        try:
            # Récupération des données
            title = self.title_entry.get().strip()
            image_url = self.image_entry.get().strip()
            excerpt = self.excerpt_text.get("1.0", "end-1c").strip()
            content = self.content_text.get("1.0", "end-1c").strip()
            
            # Préparation du payload JSON
            payload = {
                "title": title,
                "content": content,
                "excerpt": excerpt,
                "image_url": image_url
            }
            
            # Headers avec authentification
            headers = {
                "Content-Type": "application/json",
                "Authorization": f"Bearer {self.api_key}"
            }
            
            # Envoi de la requête POST
            response = requests.post(
                self.api_url,
                json=payload,
                headers=headers,
                timeout=30
            )
            
            # Traitement de la réponse
            self.after(0, self._handle_response, response)
            
        except requests.exceptions.ConnectionError:
            self.after(0, self._handle_error, 
                      "Erreur de connexion",
                      "Impossible de se connecter au serveur.\nVérifiez que l'API est accessible.")
        
        except requests.exceptions.Timeout:
            self.after(0, self._handle_error,
                      "Délai d'attente dépassé",
                      "Le serveur met trop de temps à répondre.")
        
        except requests.exceptions.RequestException as e:
            self.after(0, self._handle_error,
                      "Erreur réseau",
                      f"Une erreur est survenue lors de l'envoi:\n{str(e)}")
        
        except Exception as e:
            self.after(0, self._handle_error,
                      "Erreur inattendue",
                      f"Une erreur inattendue s'est produite:\n{str(e)}")
    
    def _handle_response(self, response):
        """Gère la réponse de l'API"""
        try:
            # Réactiver le bouton
            self.publish_button.configure(state="normal", text="🚀 Publier l'Article")
            
            # Vérifier le code de statut
            if response.status_code == 200 or response.status_code == 201:
                # Succès
                try:
                    response_data = response.json()
                    message = response_data.get("message", "Article publié avec succès!")
                except:
                    message = "Article publié avec succès!"
                
                self.update_status("✅ Article publié avec succès!", "green")
                messagebox.showinfo("Succès", message)
                
                # Proposer d'effacer le formulaire
                if messagebox.askyesno("Effacer le formulaire?", 
                                      "L'article a été publié.\nVoulez-vous effacer le formulaire ?"):
                    self.clear_form()
            
            elif response.status_code == 401:
                self.update_status("❌ Erreur d'authentification", "red")
                messagebox.showerror("Erreur d'authentification", 
                                   "La clé API est invalide ou manquante.")
            
            elif response.status_code == 400:
                try:
                    error_data = response.json()
                    error_message = error_data.get("error", "Données invalides")
                except:
                    error_message = "Les données envoyées sont invalides"
                
                self.update_status("❌ Données invalides", "red")
                messagebox.showerror("Erreur de validation", error_message)
            
            else:
                # Autre erreur
                try:
                    error_data = response.json()
                    error_message = error_data.get("error", f"Erreur HTTP {response.status_code}")
                except:
                    error_message = f"Erreur HTTP {response.status_code}"
                
                self.update_status(f"❌ Erreur {response.status_code}", "red")
                messagebox.showerror("Erreur", 
                                   f"Le serveur a retourné une erreur:\n{error_message}")
        
        except Exception as e:
            self._handle_error("Erreur de traitement", 
                             f"Erreur lors du traitement de la réponse:\n{str(e)}")
    
    def _handle_error(self, title, message):
        """Gère les erreurs"""
        self.publish_button.configure(state="normal", text="🚀 Publier l'Article")
        self.update_status(f"❌ {title}", "red")
        messagebox.showerror(title, message)


def main():
    """Point d'entrée de l'application"""
    app = BlogPublisherApp()
    app.mainloop()


if __name__ == "__main__":
    main()