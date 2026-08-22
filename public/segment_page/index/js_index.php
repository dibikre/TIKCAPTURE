// ============================================================
    // CONFIGURATION
    // ============================================================
    const API_URL = 'api_proxy.php';
    
    // ============================================================
    // STATE MANAGEMENT
    // ============================================================
    let currentData = null;
    let streamUrls = {};
    let selectedQuality = null;
    let selectedDuration = 0;
    let isRecording = false;
    let recordingTimer = null;
    let recordingStartTime = null;
    let mediaRecorder = null;
    let recordedChunks = [];
    let downloadController = null;
    let recordingMode = 'client'; // Sera récupéré depuis l'API

    // ============================================================
    // DOM ELEMENTS
    // ============================================================
    const searchForm = document.getElementById('searchForm');
    const usernameInput = document.getElementById('usernameInput');
    const searchBtn = document.getElementById('searchBtn');
    const resultsSection = document.getElementById('resultsSection');

    // ============================================================
    // UTILITY FUNCTIONS
    // ============================================================
    function extractUsername(input) {
        input = input.trim();
        const match = input.match(/tiktok\.com\/@([^\/?]+)/);
        if (match) return match[1];
        if (input.startsWith('@')) return input.substring(1);
        return input;
    }

    function formatNumber(num) {
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
        return num.toString();
    }

    function formatTime(seconds) {
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 KB';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }


    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span>${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</span>
            <span>${message}</span>
        `;
        document.getElementById('toastContainer').appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }

    // ============================================================
    // API FUNCTIONS
    // ============================================================
    async function checkApiStatus() {
        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'status'
                })
            });
            const data = await response.json();
            recordingMode = data.recording_mode || 'client';
            return data;
        } catch (error) {
            recordingMode = 'client';
            return null;
        }
    }

    async function searchLive(username) {
        const spinner = searchBtn.querySelector('.spinner');
        const text = searchBtn.querySelector('span:first-child');
        
        spinner.style.display = 'block';
        text.textContent = 'Recherche...';
        searchBtn.disabled = true;
    
        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'search',
                    username: username
                })
            });
            const data = await response.json();

            if (data.error || !data.success) {
                throw new Error(data.error || 'Erreur inconnue');
            }

            if (!data.live || !data.live.isLive) {
                if (data.user && data.user.uniqueId) {
                    // Utilisateur trouvé mais pas en live → afficher profil en mode hors ligne
                    currentData = data;
                    updateUI(data);
                    resultsSection.classList.add('active');
                    document.getElementById('profileCard').style.display = '';
                    document.getElementById('streamOptionsCard').style.display = 'none';
                    hideCorrectionPanel();
                    showToast(`@${data.user.uniqueId} n'est pas en live actuellement.`, 'info');
                } else {
                    // Utilisateur introuvable → suggestions de correction
                    resultsSection.classList.add('active');
                    document.getElementById('profileCard').style.display = 'none';
                    document.getElementById('streamOptionsCard').style.display = 'none';
                    await showCorrectionPanel(username);
                }
            } else {
                currentData = data;
                updateUI(data);
                resultsSection.classList.add('active');
                document.getElementById('profileCard').style.display = '';
                hideCorrectionPanel();
                showToast('Live trouvé !', 'success');
            }

        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            spinner.style.display = 'none';
            text.textContent = 'Rechercher';
            searchBtn.disabled = false;
        }
    }

    function updateUI(data) {
        // Profile info
        document.getElementById('profileAvatar').src = data.user.avatar || '';
        document.getElementById('profileName').textContent = data.user.nickname || '-';
        document.getElementById('profileUsername').textContent = `@${data.user.uniqueId || '-'}`;
        document.getElementById('profileBio').textContent = data.user.bio || '';
        
        // Verified badge
        const verifiedBadge = document.getElementById('verifiedBadge');
        verifiedBadge.style.display = data.user.verified ? 'flex' : 'none';
        
        const theviews = Math.max((data.live.viewers ?? 0) - 1, 0);

        // Stats
        document.getElementById('followersCount').textContent = formatNumber(data.stats.followers || 0);
        document.getElementById('followingCount').textContent = formatNumber(data.stats.following || 0);
        document.getElementById('viewersCount').textContent = formatNumber(theviews);

        // Live status
        const liveBadge = document.getElementById('liveBadge');
        const isLive = data.live.isLive;
        liveBadge.classList.toggle('offline', !isLive);
        document.getElementById('liveStatus').textContent = data.live.status;
        document.getElementById('liveTitle').textContent = data.live.title || 'Sans titre';
        document.getElementById('liveStartTime').textContent = data.live.startTime || '-';
        document.getElementById('liveViewers').textContent = `${formatNumber(theviews)} spectateurs`;

        // Stream options
        const streamOptionsCard = document.getElementById('streamOptionsCard');
        if (isLive && data.streams && Object.keys(data.streams).length > 0) {
            streamOptionsCard.style.display = 'block';
            streamUrls = data.streams;
            updateQualityOptions(data.streams);
        } else {
            streamOptionsCard.style.display = 'none';
        }

        // Thumbnail - Priorité: capture du stream > miniature profil
        const thumbnailPreview = document.getElementById('thumbnailPreview');
        const streamThumb = document.getElementById('streamThumbnail');

        if (isLive) {
            thumbnailPreview.style.display = 'block';

            if (data.live.streamThumbnail) {
                // api_proxy.php a déjà capturé la frame via ffmpeg → l'utiliser directement
                streamThumb.src = `donnees/${data.live.streamThumbnail}`;
                streamThumb.style.opacity = '1';

            } else if (data.live.thumbnail) {
                // Fallback : thumbnail TikTok fournie par l'API
                streamThumb.src = data.live.thumbnail;
                streamThumb.style.opacity = '1';

            } else {
                // Dernier recours : appel thumbnail_preview.php avec le premier stream FLV dispo
                const streamEntry = streamUrls['hd'] || streamUrls['sd'] || streamUrls['origin'] || streamUrls['ld'] || Object.values(streamUrls)[0];
                const flvUrl = streamEntry?.flv;

                if (flvUrl) {
                    streamThumb.style.opacity = '0.3';
                    streamThumb.src = data.user.avatar || '';

                    fetch('thumbnail_preview.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ stream_url: flvUrl })
                    })
                    .then(r => r.json())
                    .then(result => {
                        if (result.success && result.filename) {
                            streamThumb.src = `donnees/${result.filename}`;
                        }
                        streamThumb.style.opacity = '1';
                    })
                    .catch(() => { streamThumb.style.opacity = '1'; });

                } else {
                    thumbnailPreview.style.display = 'none';
                }
            }
        } else {
            thumbnailPreview.style.display = 'none';
        }
    }

    function updateQualityOptions(streams) {
        const grid = document.getElementById('qualityGrid');
        grid.innerHTML = '';

        const qualityOrder = ['origin', 'hd', 'sd', 'ld'];
        const qualityLabels = {
            'origin': { name: 'Original', desc: 'Qualité maximale' },
            'hd': { name: 'HD', desc: 'Haute définition' },
            'sd': { name: 'SD', desc: 'Définition standard' },
            'ld': { name: 'LD', desc: 'Basse définition' }
        };

        let isFirst = true;
        qualityOrder.forEach(key => {
            if (streams[key]) {
                const stream = streams[key];
                const option = document.createElement('div');
                option.className = 'quality-option';
                
                let desc = qualityLabels[key]?.desc || '';
                if (stream.resolution) desc = stream.resolution;
                
                option.innerHTML = `
                    <input type="radio" name="quality" value="${key}" id="quality_${key}" ${isFirst ? 'checked' : ''}>
                    <label class="quality-label" for="quality_${key}">
                        <div class="quality-name">${stream.qualityName || qualityLabels[key]?.name || key}</div>
                        <div class="quality-details">${desc}</div>
                    </label>
                    ${isFirst ? '<span class="quality-badge">Recommandé</span>' : ''}
                `;
                grid.appendChild(option);
                
                if (isFirst) {
                    selectedQuality = key;
                    isFirst = false;
                }
            }
        });

        // Add change listeners
        document.querySelectorAll('input[name="quality"]').forEach(input => {
            input.addEventListener('change', (e) => {
                selectedQuality = e.target.value;
            });
        });
    }

    // ============================================================
    // RECORDING FUNCTIONS
    // ============================================================
    
    // Duration buttons
    document.querySelectorAll('.duration-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.duration-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedDuration = parseInt(btn.dataset.duration);
            document.getElementById('customDuration').value = '';
        });
    });

    document.getElementById('customDuration').addEventListener('input', (e) => {
        const value = parseInt(e.target.value);
        if (value > 0) {
            document.querySelectorAll('.duration-btn').forEach(b => b.classList.remove('active'));
            selectedDuration = value;
        }
    });

    // Record button
    document.getElementById('recordBtn').addEventListener('click', async () => {
        if (isRecording) {
            stopRecording();
        } else {
            await startRecording();
        }
    });

    async function startRecording() {
        if (!selectedQuality || !streamUrls[selectedQuality]) {
            showToast('Veuillez sélectionner une qualité', 'error');
            return;
        }

        // Vérifier le mode d'enregistrement
        await checkApiStatus();

        if (recordingMode === 'client') {
            startClientRecording();
        } else {
            startServerRecording();
        }
    }

    async function startClientRecording() {
    try {
        isRecording = true;
        recordedChunks = [];
        recordingStartTime = Date.now();

        const recordBtn = document.getElementById('recordBtn');
        recordBtn.classList.add('recording');
        recordBtn.innerHTML = '<span>⏹️</span><span>Arrêter l\'enregistrement</span>';

        document.getElementById('recordingProgress').classList.add('active');

        const streamData = streamUrls[selectedQuality];
        
        let streamUrl = streamData.flv || streamData.hls;
        
        if (!streamUrl) {
            throw new Error('Aucune URL de stream disponible');
        }

        // Créer un nouveau controller
        downloadController = new AbortController();
                
        const response = await fetch(streamUrl, {
            signal: downloadController.signal,
            method: 'GET'
        });
        
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const reader = response.body.getReader();
        let chunks = [];
        let receivedLength = 0;
        let startTime = Date.now();
        
        // Timer pour la durée limitée
        let durationTimer = null;
        if (selectedDuration && selectedDuration > 0) {
            durationTimer = setTimeout(() => {
                downloadController.abort();
            }, selectedDuration * 1000);
        }
        
        // BOUCLE DE LECTURE
        try {
            while (true) {
                const { done, value } = await reader.read();
                
                if (done) break;
                
                chunks.push(value);
                receivedLength += value.length;
                
                const elapsed = (Date.now() - startTime) / 1000;
                
                if (selectedDuration && selectedDuration > 0) {
                    const progress = Math.min((elapsed / selectedDuration) * 100, 100);
                    document.getElementById('progressBar').style.width = `${progress}%`;
                }
                
                const elapsedMinutes = Math.floor(elapsed / 60);
                const remainingSeconds = Math.floor(elapsed % 60);
                const sizeMB = (receivedLength / (1024 * 1024)).toFixed(2);
                
                document.getElementById('progressTime').textContent = formatTime(Math.floor(elapsed));
                document.getElementById('progressSize').textContent = sizeMB + ' MB';
                
                const speed = receivedLength / elapsed;
                document.getElementById('progressSpeed').textContent = formatBytes(speed) + '/s';
            }
        } catch (readError) {
            // Si c'est un abort, ce n'est pas une vraie erreur
            if (readError.name !== 'AbortError') {
                throw readError;
            }
        }
        
        if (durationTimer) {
            clearTimeout(durationTimer);
        }
        
        // CRÉER LE BLOB ET TÉLÉCHARGER (en dehors du try/catch de lecture)
        if (chunks.length > 0) {
            const blob = new Blob(chunks, { type: 'video/x-flv' });
            const downloadUrl = URL.createObjectURL(blob);
            
            const username = currentData.user.uniqueId;
            const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
            const filename = `TikCapture_${username}_${selectedQuality}_${timestamp}.flv`;
            
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            
            // Ne pas révoquer immédiatement, laisser le temps au téléchargement
            setTimeout(() => {
                URL.revokeObjectURL(downloadUrl);
            }, 1000);
            
            const finalSize = (receivedLength / (1024 * 1024)).toFixed(2);
            const finalDuration = Math.floor((Date.now() - startTime) / 1000);
            showToast(`Enregistrement terminé ! ${finalSize} MB`, 'success');
        } else {
            showToast('Aucune donnée à télécharger', 'warning');
        }
        
    } catch (error) {
        if (error.name === 'AbortError') {
            
            // TÉLÉCHARGER MÊME EN CAS D'ABORT
            if (recordedChunks.length > 0) {
                const blob = new Blob(recordedChunks, { type: 'video/x-flv' });
                const downloadUrl = URL.createObjectURL(blob);
                
                const username = currentData?.user?.uniqueId || 'user';
                const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
                const filename = `tiktok_live_${username}_${selectedQuality}_${timestamp}_partial.flv`;
                
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                
                setTimeout(() => {
                    URL.revokeObjectURL(downloadUrl);
                }, 1000);
                
                showToast('Enregistrement partiel téléchargé', 'success');
            }
        } else {
            showToast(`Erreur: ${error.message}`, 'error');
        }
    } finally {
        stopRecording();
    }
}

    function updateProgress() {
        const totalSize = recordedChunks.reduce((sum, chunk) => sum + chunk.size, 0);
        document.getElementById('progressSize').textContent = formatBytes(totalSize);
        
        const elapsed = (Date.now() - recordingStartTime) / 1000;
        const speed = elapsed > 0 ? totalSize / elapsed : 0;
        document.getElementById('progressSpeed').textContent = formatBytes(speed) + '/s';
    }

    function downloadRecording() {
        const blob = new Blob(recordedChunks, { type: 'video/webm' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        
        const username = currentData.user.uniqueId;
        const timestamp = new Date().toISOString().replace(/:/g, '-').split('.')[0];
        a.download = `tiktok_live_${username}_${selectedQuality}_${timestamp}.webm`;
        
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        showToast('Enregistrement téléchargé !', 'success');
    }

    // ============================================================
    // SERVER-SIDE RECORDING (sur le serveur)
    // ============================================================
    async function startServerRecording() {
        try {
            isRecording = true;
            recordingStartTime = Date.now();

            const recordBtn = document.getElementById('recordBtn');
            recordBtn.classList.add('recording');
            recordBtn.innerHTML = '<span>⏹️</span><span>Arrêter l\'enregistrement</span>';

            document.getElementById('recordingProgress').classList.add('active');

            const username = currentData.user.uniqueId;
            const streamUrl = streamUrls[selectedQuality].hls;

            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'record',
                    username: username,
                    streamUrl: streamUrl,
                    quality: selectedQuality,
                    duration: selectedDuration
                })
            });

            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Erreur serveur');
            }

            // Timer pour afficher la progression
            recordingTimer = setInterval(() => {
                const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
                document.getElementById('progressTime').textContent = formatTime(elapsed);

                if (selectedDuration > 0) {
                    const progress = Math.min((elapsed / selectedDuration) * 100, 100);
                    document.getElementById('progressBar').style.width = `${progress}%`;
                    
                    if (elapsed >= selectedDuration) {
                        stopRecording();
                        // Proposer le téléchargement
                        setTimeout(() => {
                            const downloadLink = `donnees/${result.filename}`;
                            showToast('Enregistrement terminé ! Téléchargement disponible.', 'success');
                        }, 2000);
                    }
                }
            }, 1000);

        } catch (error) {
            showToast(error.message, 'error');
            stopRecording();
        }
    }

    function stopRecording() {
        isRecording = false;
        clearInterval(recordingTimer);
    
        // AJOUTÉ : Arrêter le téléchargement en cours
        if (downloadController) {
            downloadController.abort();
            downloadController = null;
        }
    
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
    
        const recordBtn = document.getElementById('recordBtn');
        recordBtn.classList.remove('recording');
        recordBtn.innerHTML = '<span>🔴</span><span>Démarrer l\'enregistrement</span>';
        
        document.getElementById('recordingProgress').classList.remove('active');
    
        // Charger les suggestions après l'enregistrement
        const username = document.getElementById('profileUsername').textContent.replace('@', '').trim();
        const keyword = username && username !== '-' ? username : 'live';
        loadSuggestionsAfterRecording(keyword);
    }
    
    // ============================================================
    // SUGGESTIONS
    // ============================================================
    async function loadSuggestions() {
        try {
            showToast('Chargement des suggestions...', 'info');
            
            const response = await fetch('tiktok_suggestions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_suggestions' })
            });
            const data = await response.json();
            
            if (!data.success || !data.suggestions || data.suggestions.length === 0) {
                throw new Error('Aucune suggestion disponible');
            }
            
            displaySuggestions(data.suggestions);
            document.getElementById('suggestionsSection').classList.add('active');
            showToast(`${data.count} lives trouvés`, 'success');
            
        } catch (error) {
            showToast('Impossible de charger les suggestions', 'error');
        }
    }

    function hideCorrectionPanel() {
        const panel = document.getElementById('correctionPanel');
        if (panel) panel.remove();
    }

    async function showCorrectionPanel(keyword) {
        hideCorrectionPanel();

        const panel = document.createElement('div');
        panel.id = 'correctionPanel';
        panel.className = 'stream-options-card';
        panel.style.cssText = 'display:block; border: 1px solid rgba(255,80,80,0.3); margin-bottom: 1.5rem;';
        panel.innerHTML = `
            <h3 class="card-title" style="color: var(--text-secondary);">
                <span>⚠️</span>
                <span>Aucun live trouvé pour <strong style="color:white;">@${keyword}</strong></span>
            </h3>
            <p style="color:var(--text-secondary); margin-bottom:1.5rem; font-size:0.9rem;">
                Veuillez corriger le nom d'utilisateur ou sélectionner parmi ces suggestions.
            </p>
            <div id="correctionGrid" class="suggestions-grid">
                <p style="color:var(--text-secondary);text-align:center;padding:1rem;">Recherche en cours...</p>
            </div>
        `;

        const resultsSection = document.getElementById('resultsSection');
        resultsSection.insertBefore(panel, resultsSection.firstChild);

        try {
            const response = await fetch('suggestion_search.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ keyword: keyword })
            });
            const data = await response.json();
            const grid = document.getElementById('correctionGrid');

            if (!data.success || !data.suggestions.length) {
                grid.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:1rem;">Aucun utilisateur trouvé.</p>';
                return;
            }

            grid.innerHTML = '';
            data.suggestions.forEach(user => {
                const card = document.createElement('div');
                card.className = 'suggestion-card';
                card.style.cursor = 'pointer';
                card.innerHTML = `
                    <div style="position:relative;">
                        <img src="${user.cover || user.avatar}" alt="${user.nickname}" class="suggestion-cover"
                             onerror="this.src='${user.avatar || 'https://tikcapture.live/assets/images/favicon.png'}'">
                    </div>
                    <div class="suggestion-info">
                        <div class="suggestion-user">
                            <img src="${user.avatar}" alt="${user.nickname}" class="suggestion-avatar"
                                 onerror="this.src='https://tikcapture.live/assets/images/favicon.png'">
                            <div class="suggestion-names">
                                <div class="suggestion-nickname">${user.nickname} ${user.verified ? '✓' : ''}</div>
                                <div class="suggestion-username">@${user.username}</div>
                            </div>
                        </div>
                        <div class="suggestion-stats">
                            <div class="suggestion-stat">
                                <span>👥</span>
                                <span>${formatNumber(user.followers)} abonnés</span>
                            </div>
                        </div>
                    </div>
                `;
                card.addEventListener('click', () => selectSuggestion(user.username));
                grid.appendChild(card);
            });

        } catch (e) {
            const grid = document.getElementById('correctionGrid');
            if (grid) grid.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:1rem;">Erreur lors du chargement.</p>';
        }
    }

    async function loadSuggestionsAfterRecording(keyword) {
        const suggestionsSection = document.getElementById('suggestionsSection');
        const suggestionsGrid = document.getElementById('suggestionsGrid');

        suggestionsSection.classList.add('active');
        suggestionsGrid.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:1rem;">Recherche de lives en cours...</p>';

        try {
            const response = await fetch('suggestion_search.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ keyword: keyword })
            });
            const data = await response.json();

            if (!data.success || !data.suggestions.length) {
                suggestionsGrid.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:1rem;">Aucun live trouvé.</p>';
                return;
            }

            suggestionsGrid.innerHTML = '';
            data.suggestions.forEach(suggestion => {
                const card = document.createElement('div');
                card.className = 'suggestion-card';
                card.onclick = () => selectSuggestion(suggestion.username);
                card.innerHTML = `
                    <div style="position: relative;">
                        <img src="${suggestion.cover || suggestion.avatar}" alt="${suggestion.nickname}" class="suggestion-cover"
                             onerror="this.src='${suggestion.avatar || 'https://tikcapture.live/assets/images/favicon.png'}'">
                        <div class="suggestion-live-badge">
                            <span style="width:6px;height:6px;background:white;border-radius:50%;animation:pulse 1s infinite;"></span>
                            EN DIRECT
                        </div>
                    </div>
                    <div class="suggestion-info">
                        <div class="suggestion-user">
                            <img src="${suggestion.avatar}" alt="${suggestion.nickname}" class="suggestion-avatar"
                                 onerror="this.src='https://tikcapture.live/assets/images/favicon.png'">
                            <div class="suggestion-names">
                                <div class="suggestion-nickname">${suggestion.nickname}</div>
                                <div class="suggestion-username">@${suggestion.username}</div>
                            </div>
                        </div>
                        <div class="suggestion-stats">
                            <div class="suggestion-stat"><span>👀</span><span>${formatNumber(suggestion.viewers)}</span></div>
                        </div>
                        ${suggestion.title ? `<div class="suggestion-title">${suggestion.title}</div>` : ''}
                    </div>
                `;
                suggestionsGrid.appendChild(card);
            });

        } catch (e) {
            suggestionsGrid.innerHTML = '<p style="color:var(--text-secondary);text-align:center;padding:1rem;">Erreur lors du chargement.</p>';
        }
    }
    
    function displaySuggestions(suggestions) {
        const grid = document.getElementById('suggestionsGrid');
        grid.innerHTML = '';
        
        suggestions.forEach(suggestion => {
            const card = document.createElement('div');
            card.className = 'suggestion-card';
            card.onclick = () => selectSuggestion(suggestion.username);
            
            card.innerHTML = `
                <div style="position: relative;">
                    <img src="${suggestion.cover || suggestion.avatar}" alt="${suggestion.nickname}" class="suggestion-cover"
                     onerror="this.src='${suggestion.avatar || 'https://tikcapture.live/assets/images/favicon.png'}'">
                    <div class="suggestion-live-badge">
                        <span style="width: 6px; height: 6px; background: white; border-radius: 50%; animation: pulse 1s infinite;"></span>
                        EN DIRECT
                    </div>
                </div>
                <div class="suggestion-info">
                    <div class="suggestion-user">
                        <img src="${suggestion.avatar}" alt="${suggestion.nickname}" class="suggestion-avatar">
                        <div class="suggestion-names">
                            <div class="suggestion-nickname">${suggestion.nickname}</div>
                            <div class="suggestion-username">@${suggestion.username}</div>
                        </div>
                    </div>
                    <div class="suggestion-stats">
                        <div class="suggestion-stat">
                            <span>👀</span>
                            <span>${formatNumber(suggestion.viewers)}</span>
                        </div>
                        <div class="suggestion-stat">
                            <span>❤️</span>
                            <span>${formatNumber(suggestion.likes)}</span>
                        </div>
                    </div>
                    ${suggestion.title ? `<div class="suggestion-title">${suggestion.title}</div>` : ''}
                </div>
            `;
            
            grid.appendChild(card);
        });
    }
    
    function selectSuggestion(username) {
        document.getElementById('usernameInput').value = username;
        hideCorrectionPanel();
        document.getElementById('profileCard').style.display = '';
        document.getElementById('suggestionsSection').classList.remove('active');
        document.getElementById('suggestionsGrid').innerHTML = '';

        showToast(`@${username} sélectionné, lancement de la recherche...`, 'success');

        setTimeout(() => {
            searchLive(username);
        }, 400);
    }
    
    // Bouton refresh suggestions
    document.getElementById('refreshSuggestionsBtn').addEventListener('click', () => {
        const username = document.getElementById('profileUsername').textContent.replace('@', '').trim();
        const keyword = username && username !== '-' ? username : 'live';
        loadSuggestionsAfterRecording(keyword);
    });

    // ============================================================
    // REFRESH & SEARCH
    // ============================================================
    document.getElementById('refreshBtn').addEventListener('click', () => {
        const username = document.getElementById('profileUsername').textContent.replace('@', '');
        if (username && username !== '-') {
            searchLive(username);
        }
    });

    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const input = usernameInput.value.trim();
        if (input) {
            hideCorrectionPanel();
            document.getElementById('profileCard').style.display = '';
            const username = extractUsername(input);
            searchLive(username);
        }
    });

    // -----------------------------------------------------------
    // INITIALIZATION
    // -----------------------------------------------------------
    (async function init() {
        await checkApiStatus();
    
        // Auto-fill depuis paramètre URL (?u=...)
        const params = new URLSearchParams(window.location.search);
        const u = params.get('u');
        if (u) {
            const username = extractUsername(u);
            usernameInput.value = u;
    
            await new Promise(resolve => setTimeout(resolve, 1300));
            await searchLive(username);
        }
    })();

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

        // Fermer le menu si on clique en dehors (mais pas sur les boutons du menu)
        document.addEventListener('click', (e) => {
            if (!mobileMenuToggle.contains(e.target) && 
                !mobileDropdown.contains(e.target)) {
                mobileMenuToggle.classList.remove('active');
                mobileDropdown.classList.remove('active');
            }
        });

        // NE PLUS fermer le menu quand on clique sur un bouton du menu
        // Cette section a été supprimée pour garder le menu ouvert
    }
    
    // ============================================================
    // LAZY LOADING IMAGES
    // ============================================================
    //const observerOptions = {
    //    root: null,
    //    rootMargin: '50px',
    //    threshold: 0.1
    //};
//
    //const imageObserver = new IntersectionObserver((entries, observer) => {
    //    entries.forEach(entry => {
    //        if (entry.isIntersecting) {
    //            const container = entry.target;
    //            const dataSrc = container.dataset.src;
    //            
    //            if (dataSrc) {
    //                const img = document.createElement('img');
    //                img.src = dataSrc;
    //                img.alt = 'TikCapture Feature';
    //                
    //                img.onload = () => {
    //                    container.appendChild(img);
    //                    setTimeout(() => {
    //                        img.classList.add('loaded');
    //                        const placeholder = container.querySelector('.image//-placeholder');
    //                        if (placeholder) {
    //                            placeholder.style.opacity = '0';
    //                            setTimeout(() => placeholder.remove(), 500);
    //                        }
    //                    }, 100);
    //                };
    //                
    //                img.onerror = () => {
    //                    const placeholder = container.querySelector('.image//-placeholder');
    //                    if (placeholder) {
    //                        placeholder.innerHTML = '<span style="color: var//(--text-muted);">Image non disponible</span>';
    //                    }
    //                };
    //            }
    //            
    //            observer.unobserve(container);
    //        }
    //    });
    //}, observerOptions);
//
    //// Observer toutes les images lazy
    //document.addEventListener('DOMContentLoaded', () => {
    //    document.querySelectorAll('.lazy-image').forEach(img => {
    //        imageObserver.observe(img);
    //    });
    //});