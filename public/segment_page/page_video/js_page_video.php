$('#searchForm').on('submit', function(e) {
            e.preventDefault();
            
            var $btn = $('#searchBtn');
            var url = $('#tiktokUrl').val().trim();
            
            if (!url) return;
            
            // État loading
            $btn.addClass('loading').prop('disabled', true);
            $btn.find('.btn-text').text('Chargement...');
            
            $.ajax({
                url: window.location.pathname,
                method: 'POST',
                data: { 'tiktok-url': url },
                dataType: 'html',
                success: function(response) {
                    // Parser la réponse HTML
                    var $response = $('<div>').html(response);
                    var $newResult = $response.find('#result');
                    
                    if ($newResult.length) {
                        // Remplacer ou ajouter la section résultat
                        if ($('#result').length) {
                            $('#result').replaceWith($newResult);
                        } else {
                            $('.search-section').after($newResult);
                        }
                        
                        // Scroll vers résultat
                        $('html, body').animate({
                            scrollTop: $('#result').offset().top - 100
                        }, 800);
                        
                        // Chercher et exécuter le script des boutons de téléchargement
                        var scriptMatch = response.match(/<script>([\s\S]*?\$\('#sans_filigrane_link'\)[\s\S]*?)<\/script>/);
                        if (scriptMatch && scriptMatch[1]) {
                            try {
                                // Extraire seulement la partie des boutons téléchargement
                                var scriptContent = scriptMatch[1];
                                // Retirer le $(document).ready wrapper si présent
                                scriptContent = scriptContent.replace(/\$\(document\)\.ready\(function\(\)\s*\{/, '');
                                scriptContent = scriptContent.replace(/\}\);[\s]*$/, '');
                                eval(scriptContent);
                            } catch(e) {
                                console.error('Erreur script:', e);
                            }
                        }
                    } else {
                        // Vérifier s'il y a une erreur
                        var $error = $response.find('.alert-danger');
                        if ($error.length) {
                            if ($('#result').length) {
                                $('#result').html($error);
                            } else {
                                $('.search-section').after('<section class="results-section active" id="result">' + $error.prop('outerHTML') + '</section>');
                            }
                        }
                    }
                },
                error: function() {
                    alert('Erreur lors de la recherche. Veuillez réessayer.');
                },
                complete: function() {
                    $btn.removeClass('loading').prop('disabled', false);
                    $btn.find('.btn-text').text('Rechercher');
                }
            });
        });
        
        // Fonction pour charger une vidéo recommandée
        function loadVideo(url) {
            // Remplir le champ avec l'URL
            document.getElementById('tiktokUrl').value = url;
            
            // Scroller vers le formulaire
            $('html, body').animate({
                scrollTop: ($('.search-section').offset().top - 100)
            }, 800);
            
            // Focus sur le champ
            setTimeout(function() {
                document.getElementById('tiktokUrl').focus();
                // Effet visuel
                document.getElementById('tiktokUrl').style.borderColor = 'var(--accent-primary)';
                document.getElementById('tiktokUrl').style.boxShadow = '0 0 0 4px rgba(254, 44, 85, 0.2)';
                setTimeout(function() {
                    document.getElementById('tiktokUrl').style.borderColor = '';
                    document.getElementById('tiktokUrl').style.boxShadow = '';
                }, 1500);
            }, 900);
        }
        
        // Placeholder animation
        const placeholders = [
            "https://www.tiktok.com/@username/video/1234567890123456789",
            "https://vm.tiktok.com/a1b2c3/"
        ];
        let currentIndex = 0;
        
        setInterval(function() {
            currentIndex = (currentIndex + 1) % placeholders.length;
            document.getElementById('tiktokUrl').placeholder = placeholders[currentIndex];
        }, 3000);
        
        // ============================================================
        // MOBILE MENU TOGGLE
        // ============================================================
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileDropdown = document.getElementById('mobileDropdown');
        
        if (mobileMenuToggle && mobileDropdown) {
            mobileMenuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                mobileMenuToggle.classList.toggle('active');
                mobileDropdown.classList.toggle('active');
            });
        
            document.addEventListener('click', (e) => {
                if (!mobileMenuToggle.contains(e.target) && 
                    !mobileDropdown.contains(e.target)) {
                    mobileMenuToggle.classList.remove('active');
                    mobileDropdown.classList.remove('active');
                }
            });
        }
        
        // ============================================================
        // LAZY LOADING IMAGES
        // ============================================================
        const observerOptions = {
            root: null,
            rootMargin: '50px',
            threshold: 0.1
        };
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const container = entry.target;
                    const dataSrc = container.dataset.src;
                    
                    if (dataSrc) {
                        const img = document.createElement('img');
                        img.src = dataSrc;
                        img.alt = 'TikSave Feature';
                        
                        img.onload = () => {
                            container.appendChild(img);
                            setTimeout(() => {
                                img.classList.add('loaded');
                                const placeholder = container.querySelector('.image-placeholder');
                                if (placeholder) {
                                    placeholder.style.opacity = '0';
                                    setTimeout(() => placeholder.remove(), 500);
                                }
                            }, 100);
                        };
                        
                        img.onerror = () => {
                            const placeholder = container.querySelector('.image-placeholder');
                            if (placeholder) {
                                placeholder.innerHTML = '<span style="color: var(--text-muted);">Image non disponible</span>';
                            }
                        };
                    }
                    
                    observer.unobserve(container);
                }
            });
        }, observerOptions);
        
        // Observer toutes les images lazy
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.lazy-image').forEach(img => {
                imageObserver.observe(img);
            });
        });