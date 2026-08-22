* {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --bg-primary: #121212;
        --bg-secondary: #1e1e1e;
        --bg-tertiary: #2a2a2a;
        --bg-card: #1a1a1a;
        --accent-primary: #fe2c55;
        --accent-secondary: #25f4ee;
        --accent-gradient: linear-gradient(135deg, #fe2c55 0%, #ff6b6b 100%);
        --text-primary: #ffffff;
        --text-secondary: #a0a0a0;
        --text-muted: #666666;
        --border-color: #333333;
        --success: #00c853;
        --warning: #ffc107;
        --error: #ff5252;
        --shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        --shadow-glow: 0 0 40px rgba(254, 44, 85, 0.2);
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--bg-primary);
        color: var(--text-primary);
        min-height: 100vh;
        line-height: 1.6;
    }

    .header {
        background: rgba(30, 30, 30, 0.5);
        border-bottom: 1px solid var(--border-color);
        padding: 1rem 2rem;
        position: sticky;
        top: 0;
        z-index: 1000;  /* ← AUGMENTER à 1000 */
        backdrop-filter: blur(10px);
        overflow: visible;  /* ← AJOUTER cette ligne */
    }

    .header-stars {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        overflow: hidden;
        opacity: 0.6;
    }

    .header-stars #stars {
        position: absolute;
        background: transparent;
        width: 200rem;
        height: 200rem;
    }

    .header-stars #stars::after {
        content: "";
        position: absolute;
        top: -10rem;
        left: -100rem;
        width: 100%;
        height: 100%;
        animation: animation: animStarRotate 180s linear infinite;
        background-image: radial-gradient(#ffffff 1px, transparent 1%);
        background-size: 50px 50px;
    }

    .header-stars #stars::before {
        content: "";
        position: absolute;
        top: 0;
        left: -50%;
        width: 170%;
        height: 500%;
        animation: animStar 60s linear infinite;
        background-image: radial-gradient(#ffffff 1px, transparent 1%);
        background-size: 50px 50px;
        opacity: 0.5;
    }

    .header-content {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        position: relative;
        z-index: 1;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .logo-icon {
        width: 40px;
        height: 40px;
        background: var(--accent-gradient);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .logo-text {
        font-size: 1.25rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .header-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--text-secondary);
        font-size: 0.875rem;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--success);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    /* Navigation Header */
    .header-nav {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex: 1;
        justify-content: center;
    }

    .btn-accent {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 10rem;
        height: 2.5rem;
        cursor: pointer;
        backdrop-filter: blur(1rem);
        border-radius: 5rem;
        transition: 0.5s;
        border: double 3px transparent;
        background-image: linear-gradient(#212121, #212121), linear-gradient(137.48deg, #42413e 10%, #000 45%, #620707 67%, #1b2d60 87%);
        background-origin: border-box;
        background-clip: content-box, border-box;
        padding: 0;
    }

    .btn-accent strong {
        z-index: 2;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        letter-spacing: 3px;
        color: #ffffff !important;
        text-shadow: none;
        font-weight: 700;
    }

    .btn-accent:hover {
        transform: scale(1.1);
    }

    .btn-accent:active {
        border: double 4px #fe53bb;
        background-origin: border-box;
        background-clip: content-box, border-box;
        animation: none;
    }

    .btn-accent:active .circle {
        background: #fe53bb;
    }

    #stars {
        position: relative;
        background: transparent;
        width: 200rem;
        height: 200rem;
    }

    #stars::after {
        content: "";
        position: absolute;
        top: -10rem;
        left: -100rem;
        width: 100%;
        height: 100%;
        animation: animStarRotate 90s linear infinite;
    }

    #stars::after {
        background-image: radial-gradient(#ffffff 1px, transparent 1%);
        background-size: 50px 50px;
    }

    #stars::before {
        content: "";
        position: absolute;
        top: 0;
        left: -50%;
        width: 170%;
        height: 500%;
        animation: animStar 60s linear infinite;
    }

    #stars::before {
        background-image: radial-gradient(#ffffff 1px, transparent 1%);
        background-size: 50px 50px;
        opacity: 0.5;
    }

    @keyframes animStar {
        from {
            transform: translateY(0);
        }
        to {
            transform: translateY(-135rem);
        }
    }

    @keyframes animStarRotate {
        from {
            transform: rotate(360deg);
        }
        to {
            transform: rotate(0);
        }
    }

    @keyframes gradient_301 {
        0% {
            background-position: 0% 50%;
        }
        50% {
            background-position: 100% 50%;
        }
        100% {
            background-position: 0% 50%;
        }
    }

    @keyframes pulse_3011 {
        0% {
            transform: scale(0.75);
            box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 10px rgba(0, 0, 0, 0);
        }
        100% {
            transform: scale(0.75);
            box-shadow: 0 0 0 0 rgba(0, 0, 0, 0);
        }
    }

    /* Boutons Menu verts */
    .b-menu {
        outline: none;
        cursor: pointer;
        border: none;
        padding: 0.65rem 1.5rem;
        margin: 0;
        font-family: 'Inter', sans-serif;
        position: relative;
        display: inline-block;
        letter-spacing: 0.02rem;
        font-weight: 600;
        font-size: 14px;
        border-radius: 500px;
        overflow: hidden;
        background: #1e1e1e;
        color: #000;
        border: 1px solid #333333;
        transition: all 0.3s ease;
    }

    .b-menu span {
        position: relative;
        z-index: 10;
        transition: color 0.4s;
    }

    .b-menu:hover span {
        color: #66ff66;
    }

    .b-menu::before,
    .b-menu::after {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
    }

    .b-menu::before {
        content: "";
        background: #66ff66;
        width: 120%;
        left: -10%;
        transform: skew(30deg);
        transition: transform 0.4s cubic-bezier(0.3, 1, 0.8, 1);
    }

    .b-menu:hover::before {
        transform: translate3d(100%, 0, 0);
    }

    /* Menu Hamburger Mobile */
    .mobile-menu-toggle {
        display: none;
        flex-direction: column;
        gap: 4px;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        padding: 0.5rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 10;
        position: relative;
    }

    .mobile-menu-toggle span {
        width: 20px;
        height: 2px;
        background: var(--text-primary);
        transition: all 0.3s ease;
    }

    .mobile-menu-toggle.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }

    .mobile-menu-toggle.active span:nth-child(2) {
        opacity: 0;
    }

    .mobile-menu-toggle.active span:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -5px);
    }

    /* Dropdown Menu Mobile */
    .mobile-dropdown {
        position: fixed;
        top: 70px;
        left: 0;
        right: 0;
        background: rgba(30, 30, 30, 0.95);
        border-bottom: 1px solid var(--border-color);
        z-index: 999;  /* ← AUGMENTER à 999 au lieu de 99 */
        box-shadow: var(--shadow);
        backdrop-filter: blur(10px);
        max-height: 0;
        padding: 0 1rem;
        overflow: hidden;
        opacity: 0;
        transition: max-height 0.4s ease, opacity 0.4s ease, padding 0.4s ease;
    }

    .mobile-dropdown.active {
        max-height: 500px;
        padding: 1rem;
        opacity: 1;
    }

    .mobile-dropdown-content {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .mobile-dropdown .btn-accent,
    .mobile-dropdown .b-menu {
        width: 100%;
        justify-content: center;
    }

    /* Main Container */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    /* Search Section */
    .search-section {
        background: var(--bg-card);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
    }

    .search-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .search-subtitle {
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
    }

    .search-form {
        display: flex;
        gap: 1rem;
    }

    .input-wrapper {
        flex: 1;
        position: relative;
    }

    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 1.25rem;
    }

    .search-input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        background: var(--bg-tertiary);
        border: 2px solid var(--border-color);
        border-radius: 12px;
        color: var(--text-primary);
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 4px rgba(254, 44, 85, 0.1);
    }

    .search-input::placeholder {
        color: var(--text-muted);
    }

    .btn {
        padding: 1rem 2rem;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: var(--accent-gradient);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-glow);
    }

    .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-secondary {
        background: var(--bg-tertiary);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
    }

    .btn-secondary:hover {
        background: var(--border-color);
    }

    /* Loading Spinner */
    .spinner {
        width: 20px;
        height: 20px;
        border: 2px solid transparent;
        border-top-color: currentColor;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Results Section */
    .results-section {
        display: none;
    }

    .results-section.active {
        display: block;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .content-block.single-column {
        grid-template-columns: 1fr;
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Profile Card */
    .profile-card {
        background: var(--bg-card);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
    }

    .profile-header {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 3px solid var(--accent-primary);
        object-fit: cover;
    }

    .profile-info {
        flex: 1;
    }

    .profile-name {
        font-size: 1.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .verified-badge {
        width: 20px;
        height: 20px;
        background: var(--accent-secondary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
    }

    .profile-username {
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
    }

    .profile-bio {
        color: var(--text-secondary);
        font-size: 0.9rem;
        max-width: 500px;
    }

    .profile-stats {
        display: flex;
        gap: 2rem;
        padding: 1rem 0;
        border-top: 1px solid var(--border-color);
        border-bottom: 1px solid var(--border-color);
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .stat-label {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }

    /* Live Status Card */
    .live-status-card {
        background: linear-gradient(135deg, rgba(254, 44, 85, 0.1) 0%, rgba(37, 244, 238, 0.1) 100%);
        border-radius: 16px;
        padding: 1.5rem;
        margin-top: 1.5rem;
        border: 1px solid rgba(254, 44, 85, 0.3);
    }

    .live-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--accent-primary);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }

    .live-badge.offline {
        background: var(--text-muted);
    }

    .live-dot {
        width: 8px;
        height: 8px;
        background: white;
        border-radius: 50%;
        animation: pulse 1s infinite;
    }

    .live-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .live-meta {
        display: flex;
        gap: 1.5rem;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .live-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Stream Options Card */
    .stream-options-card {
        background: var(--bg-card);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .card-title-icon {
        width: 36px;
        height: 36px;
        background: var(--bg-tertiary);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Quality Options */
    .quality-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .quality-option {
        position: relative;
    }

    .quality-option input {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    .quality-label {
        display: block;
        padding: 1.25rem;
        background: var(--bg-tertiary);
        border: 2px solid var(--border-color);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .quality-option input:checked + .quality-label {
        border-color: var(--accent-primary);
        background: rgba(254, 44, 85, 0.1);
    }

    .quality-option:hover .quality-label {
        border-color: var(--accent-primary);
    }

    .quality-name {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .quality-details {
        font-size: 0.875rem;
        color: var(--text-secondary);
    }

    .quality-badge {
        position: absolute;
        top: -8px;
        right: 8px;
        background: var(--accent-gradient);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* Duration Input */
    .duration-section {
        margin-bottom: 2rem;
    }

    .duration-label {
        display: block;
        font-weight: 500;
        margin-bottom: 0.75rem;
    }

    .duration-options {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .duration-btn {
        padding: 0.75rem 1.25rem;
        background: var(--bg-tertiary);
        border: 2px solid var(--border-color);
        border-radius: 10px;
        color: var(--text-primary);
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .duration-btn:hover,
    .duration-btn.active {
        border-color: var(--accent-primary);
        background: rgba(254, 44, 85, 0.1);
    }

    .custom-duration {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .duration-input {
        width: 100px;
        padding: 0.75rem 1rem;
        background: var(--bg-tertiary);
        border: 2px solid var(--border-color);
        border-radius: 10px;
        color: var(--text-primary);
        font-size: 0.9rem;
    }

    .duration-input:focus {
        outline: none;
        border-color: var(--accent-primary);
    }

    /* Record Actions */
    .record-actions {
        display: flex;
        gap: 1rem;
    }

    .btn-record {
        flex: 1;
        padding: 1.25rem 2rem;
        font-size: 1.1rem;
    }

    .btn-record.recording {
        background: var(--error);
        animation: recording-pulse 1.5s infinite;
    }

    @keyframes recording-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(255, 82, 82, 0.4); }
        50% { box-shadow: 0 0 0 15px rgba(255, 82, 82, 0); }
    }

    /* Recording Progress */
    .recording-progress {
        display: none;
        background: var(--bg-card);
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow);
    }

    .recording-progress.active {
        display: block;
        animation: fadeIn 0.5s ease;
    }
    
    /* Suggestions Grid */
    .suggestions-section {
        display: none;
        margin-top: 2rem;
    }
    
    .suggestions-section.active {
        display: block;
        animation: fadeIn 0.5s ease;
    }
    
    .suggestions-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }
    
    .suggestions-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.25rem;
        font-weight: 600;
    }
    
    .suggestions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1rem;
    }
    
    .suggestion-card {
        background: var(--bg-tertiary);
        border: 2px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .suggestion-card:hover {
        border-color: var(--accent-primary);
        transform: translateY(-4px);
        box-shadow: var(--shadow-glow);
    }
    
    .suggestion-cover {
        width: 100%;
        height: 160px;
        object-fit: cover;
        position: relative;
    }
    
    .suggestion-live-badge {
        position: absolute;
        top: 0.5rem;
        left: 0.5rem;
        background: var(--accent-primary);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .suggestion-info {
        padding: 1rem;
    }
    
    .suggestion-user {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
    }
    
    .suggestion-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid var(--accent-primary);
        object-fit: cover;
    }
    
    .suggestion-names {
        flex: 1;
        min-width: 0;
    }
    
    .suggestion-nickname {
        font-weight: 600;
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .suggestion-username {
        color: var(--text-secondary);
        font-size: 0.85rem;
    }
    
    .suggestion-stats {
        display: flex;
        gap: 1rem;
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-top: 0.5rem;
    }
    
    .suggestion-stat {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .suggestion-title {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-top: 0.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .progress-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 600;
    }

    .recording-indicator {
        width: 12px;
        height: 12px;
        background: var(--accent-primary);
        border-radius: 50%;
        animation: pulse 1s infinite;
    }

    .progress-time {
        font-size: 1.5rem;
        font-weight: 700;
        font-family: 'SF Mono', 'Consolas', monospace;
    }

    .progress-bar-container {
        background: var(--bg-tertiary);
        border-radius: 10px;
        height: 8px;
        overflow: hidden;
        margin-bottom: 1rem;
    }

    .progress-bar {
        height: 100%;
        background: var(--accent-gradient);
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    .progress-stats {
        display: flex;
        justify-content: space-between;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    /* Toast Notifications */
    .toast-container {
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 1000;
    }

    .toast {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        box-shadow: var(--shadow);
        animation: slideIn 0.3s ease;
        max-width: 350px;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateX(100px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .toast.success { border-left: 4px solid var(--success); }
    .toast.error { border-left: 4px solid var(--error); }
    .toast.info { border-left: 4px solid var(--accent-secondary); }

    .thumbnail-preview {
      margin-top: 1.5rem;
      border-radius: 12px;
      overflow: hidden;
      position: relative;
    
      aspect-ratio: 1 / 1;   /* Rend le bloc carré */
      width: 100%;           /* ou largeur fixe ex: 300px */
    }
    
    .thumbnail-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;     /* Coupe l’image sans la déformer */
    }

    .thumbnail-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        top: 5px;
        padding: 1rem;
        background: linear-gradient(transparent, rgba(0,0,0,0.8));
        }

        @media (max-width: 1200px) {
            .header {
                padding: 0.75rem 1rem;
            }

            .header-content {
                flex-wrap: wrap;
                gap: 1rem;
                max-width: 100%;
            }

            .logo {
                order: 1;
                flex-shrink: 0;
            }

            .mobile-menu-toggle {
                display: flex;
                order: 2;
                margin-left: auto;
            }

            .header-nav {
                display: none !important;
            }

            .header-status {
                display: none !important;
            }
        }

    @media (max-width: 768px) {
        .header {
            padding: 0.75rem 1rem;
        }

        .header-content {
            gap: 0.75rem;
        }

        .logo-text {
            font-size: 1rem;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            font-size: 1rem;
        }

        .header-status span:last-child {
            display: none;
        }

        .btn-accent {
            width: 100%;
            height: 2.5rem;
        }

        .container {
            padding: 1rem;
        }

        .search-form {
            flex-direction: column;
        }

        .profile-header {
            flex-direction: column;
            text-align: center;
        }

        .profile-stats {
            justify-content: center;
        }

        .quality-grid {
            grid-template-columns: 1fr;
        }

        .record-actions {
            flex-direction: column;
        }

        .suggestions-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    .empty-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .empty-text {
        color: var(--text-secondary);
    }
    
    /* ============================================================
       FOOTER STYLES
       ============================================================ */
    .footer {
        background: var(--bg-card);
        border-top: 1px solid var(--border-color);
        margin-top: 4rem;
        padding: 3rem 0 1.5rem;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }

    .footer-top {
        display: grid;
        grid-template-columns: 1.5fr 2fr;
        gap: 3rem;
        padding-bottom: 2.5rem;
        border-bottom: 1px solid var(--border-color);
        margin-bottom: 2rem;
    }

    .footer-brand {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .footer-logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.5rem;
    }

    .footer-tagline {
        color: var(--text-secondary);
        font-size: 0.95rem;
        line-height: 1.6;
        max-width: 350px;
    }

    .footer-social {
        display: flex;
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .social-link {
        width: 40px;
        height: 40px;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        transition: all 0.3s ease;
    }

    .social-link:hover {
        border-color: var(--accent-primary);
        color: var(--accent-primary);
        transform: translateY(-2px);
    }

    .social-link svg {
        stroke: currentColor;
        fill: none;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .footer-links {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }

    .footer-column {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .footer-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .footer-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .footer-link {
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: inline-block;
    }

    .footer-link:hover {
        color: var(--accent-primary);
        transform: translateX(4px);
    }

    .footer-bottom {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .footer-bottom-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .footer-copyright {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .footer-copyright strong {
        color: var(--text-primary);
        background: var(--accent-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .footer-badges {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .footer-badge {
        padding: 0.4rem 0.9rem;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        font-size: 0.8rem;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .footer-disclaimer {
        color: var(--text-primary);
        font-size: 0.85rem;
        text-align: center;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
        line-height: 1.5;
    }

    /* Footer Responsive */
    @media (max-width: 968px) {
        .footer-top {
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .footer-links {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .footer {
            padding: 2rem 0 1rem;
        }

        .footer-container {
            padding: 0 1rem;
        }

        .footer-links {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .footer-bottom-content {
            flex-direction: column;
            align-items: flex-start;
        }

        .footer-badges {
            width: 100%;
        }

        .footer-badge {
            flex: 1;
            text-align: center;
        }
    }
    
    /* ============================================================
       CONTENT SECTION STYLES
       ============================================================ */
    .content-section {
        background: var(--bg-primary);
        padding: 4rem 0;
        margin-top: 3rem;
    }

    .content-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2rem;
    }

    .content-block {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 3rem;
        margin-bottom: 5rem;
        align-items: center;
    }

    .content-block.reverse-block {
        direction: rtl;
    }

    .content-block.reverse-block > * {
        direction: ltr;
    }

    .content-text {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .content-text.full-width {
        grid-column: 1 / -1;
    }

    .content-title {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 1rem;
        line-height: 1.3;
    }

    .content-title.centered {
        justify-content: center;
    }

    .title-icon {
        font-size: 2.5rem;
        filter: drop-shadow(0 0 20px rgba(254, 44, 85, 0.3));
    }

    .content-paragraph {
        color: var(--text-secondary);
        font-size: 1.05rem;
        line-height: 1.8;
    }

    .content-paragraph.centered-text {
        text-align: center;
    }

    .content-paragraph.large-text {
        font-size: 1.2rem;
        color: var(--text-primary);
    }

    .content-paragraph strong {
        color: var(--text-primary);
        font-weight: 600;
    }

    .content-highlights {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin-top: 1rem;
    }

    .highlight-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.25rem;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .highlight-item:hover {
        border-color: var(--accent-primary);
        transform: translateX(8px);
    }

    .highlight-icon {
        font-size: 2rem;
        min-width: 50px;
        text-align: center;
    }

    .highlight-item h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }

    .highlight-item p {
        font-size: 0.95rem;
        color: var(--text-secondary);
    }

    /* Lazy Loading Images */
    .content-image {
        position: relative;
        width: 100%;
        height: 400px;
        border-radius: 16px;
        overflow: hidden;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
    }

    .content-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0;
        transition: opacity 0.5s ease;
    }

    .content-image img.loaded {
        opacity: 1;
    }

    .image-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--bg-secondary) 100%);
    }

    .placeholder-spinner {
        width: 50px;
        height: 50px;
        border: 3px solid var(--border-color);
        border-top-color: var(--accent-primary);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    /* Steps Container */
    .steps-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-top: 1rem;
    }

    .step-item {
        display: flex;
        gap: 1.5rem;
        padding: 1.5rem;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .step-item:hover {
        border-color: var(--accent-primary);
        box-shadow: var(--shadow-glow);
    }

    .step-number {
        min-width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--accent-gradient);
        color: white;
        font-size: 1.5rem;
        font-weight: 700;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .step-content h3 {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .step-content p {
        color: var(--text-secondary);
        line-height: 1.7;
    }
    
    /* Features Grid */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
        grid-column: 1 / -1;
    }
    
    .feature-card {
        padding: 2rem;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        transition: all 0.3s ease;
    }
    
    .feature-card:hover {
        border-color: var(--accent-primary);
        transform: translateY(-5px);
        box-shadow: var(--shadow-glow);
    }
    
    .feature-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        display: block;
    }
    
    .feature-card h3 {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.75rem;
    }
    
    .feature-card p {
        color: var(--text-secondary);
        line-height: 1.7;
    }
    
    /* Content List */
    .content-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .content-list li {
        padding-left: 2rem;
        position: relative;
        color: var(--text-secondary);
        line-height: 1.8;
    }
    
    .content-list li::before {
        content: "✓";
        position: absolute;
        left: 0;
        color: var(--accent-primary);
        font-weight: 700;
        font-size: 1.2rem;
    }
    
    .content-list li strong {
        color: var(--text-primary);
    }
    
    /* Use Cases */
    .use-cases {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }
    
    .use-case-item {
        padding: 1.5rem;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .use-case-item:hover {
        border-color: var(--accent-primary);
        transform: translateY(-3px);
    }
    
    .use-case-item h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.75rem;
    }
    
    .use-case-item p {
        color: var(--text-secondary);
        line-height: 1.7;
        font-size: 0.95rem;
    }
    
    /* Technical Grid */
    .technical-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
        grid-column: 1 / -1;
    }
    
    .technical-card {
        padding: 1.75rem;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-left: 4px solid var(--accent-primary);
        border-radius: 12px;
    }
    
    .technical-card h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.75rem;
    }
    
    .technical-card p {
        color: var(--text-secondary);
        line-height: 1.7;
        font-size: 0.95rem;
    }
    
    /* FAQ Grid */
    .faq-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
        grid-column: 1 / -1;
    }
    
    .faq-item {
        padding: 1.75rem;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .faq-item:hover {
        border-color: var(--accent-primary);
    }
    
    .faq-item h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.75rem;
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .faq-item h4::before {
        content: "Q:";
        color: var(--accent-primary);
        font-weight: 700;
        flex-shrink: 0;
    }
    
    .faq-item p {
        color: var(--text-secondary);
        line-height: 1.7;
        font-size: 0.95rem;
    }
    
    /* Tips List */
    .tips-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-top: 1rem;
    }
    
    .tip-item {
        display: flex;
        gap: 1.5rem;
        padding: 1.5rem;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
    }
    
    .tip-number {
        min-width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-secondary);
        border: 2px solid var(--accent-primary);
        color: var(--accent-primary);
        font-size: 1.3rem;
        font-weight: 700;
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .tip-item h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }
    
    .tip-item p {
        color: var(--text-secondary);
        line-height: 1.7;
    }
    
    /* CTA Block */
    .cta-block {
        background: linear-gradient(135deg, rgba(254, 44, 85, 0.1) 0%, rgba(37, 244, 238, 0.1) 100%);
        border: 1px solid rgba(254, 44, 85, 0.3);
        border-radius: 20px;
        padding: 3rem 2rem;
        margin-top: 3rem;
        margin-bottom: 2rem;
    }
    
    .cta-block .content-text.centered {
        text-align: center;
    }
    
    .cta-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin: 2rem 0;
        flex-wrap: wrap;
    }
    
    .btn-large {
        padding: 1.25rem 2.5rem;
        font-size: 1.1rem;
    }
    
    .cta-stats {
        display: flex;
        gap: 2rem;
        justify-content: center;
        margin-top: 2rem;
        flex-wrap: wrap;
    }
    
    .stat-badge {
        text-align: center;
        padding: 1rem 1.5rem;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
    }
    
    .stat-badge-value {
        font-size: 2rem;
        font-weight: 700;
        background: var(--accent-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stat-badge-label {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-top: 0.25rem;
    }
    
    /* Responsive Content Section */
    @media (max-width: 968px) {
        .content-block {
            grid-template-columns: 1fr;
            gap: 2rem;
        }
    
        .content-block.reverse-block {
            direction: ltr;
        }
    
        .content-image {
            height: 300px;
        }
    
        .content-title {
            font-size: 1.6rem;
        }
    
        .features-grid,
        .use-cases,
        .technical-grid,
        .faq-grid {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 640px) {
        .content-section {
            padding: 2rem 0;
        }
    
        .content-container {
            padding: 0 1rem;
        }
    
        .content-title {
            font-size: 1.4rem;
            flex-direction: column;
            text-align: center;
        }
    
        .content-paragraph {
            font-size: 1rem;
        }
    
        .highlight-item {
            flex-direction: column;
            text-align: center;
        }
    
        .step-item {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
    
        .cta-buttons {
            flex-direction: column;
            width: 100%;
        }
    
        .btn-large {
            width: 100%;
        }
    }
    
    /* ============================================================
   SVG ICONS STYLES
   ============================================================ */

/* Container pour les icônes SVG */
.svg-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
    flex-shrink: 0;
}

.svg-icon svg {
    width: 100%;
    height: 100%;
    fill: currentColor;
    stroke: none;
}

/* Variantes de tailles */
.svg-icon.icon-sm {
    width: 0.875em;
    height: 0.875em;
}

.svg-icon.icon-lg {
    width: 1.5em;
    height: 1.5em;
}

.svg-icon.icon-xl {
    width: 2em;
    height: 2em;
}

.svg-icon.icon-2xl {
    width: 2.5em;
    height: 2.5em;
}

/* Pour les icônes avec stroke au lieu de fill */
.svg-icon.stroke-icon svg {
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* Icônes colorées (optionnel) */
.svg-icon.icon-primary svg {
    fill: var(--accent-primary);
}

.svg-icon.icon-secondary svg {
    fill: var(--accent-secondary);
}

.svg-icon.icon-gradient svg {
    fill: url(#iconGradient);
}

/* Animation optionnelle */
.svg-icon.icon-animated {
    transition: transform 0.3s ease;
}

.svg-icon.icon-animated:hover {
    transform: scale(1.1);
}

/* Adapté pour vos classes existantes */
.title-icon .svg-icon {
    width: 1em;
    height: 1em;
}

.highlight-icon .svg-icon {
    width: 1em;
    height: 1em;
}

.feature-icon .svg-icon {
    width: 1em;
    height: 1em;
}

.tip-number .svg-icon {
    width: 1em;
    height: 1em;
}

.suggestion-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: var(--card-bg, #1a1a2e);
    border-radius: 12px;
    border: 1px solid var(--border-color, rgba(255,255,255,0.08));
    transition: background 0.2s, transform 0.15s;
}
.suggestion-card:hover {
    background: var(--hover-bg, rgba(255,255,255,0.06));
    transform: translateY(-2px);
}

.extension-banner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.2rem;
    margin-top: 1.2rem;
    padding: 0.9rem 1.4rem;
    background: rgba(24, 15, 15, 0.93);
    border: 1px solid rgba(255, 60, 60, 0.2);
    border-radius: 12px;
    flex-wrap: wrap;
}
.extension-text {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0;
    text-align: center;
}
.btn-extension {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #e0002a;
    color: white;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 0.55rem 1.1rem;
    border-radius: 8px;
    text-decoration: none;
    white-space: nowrap;
    transition: background 0.2s, transform 0.15s;
}
.btn-extension:hover {
    background: white;
    color: #e0002a;
    transform: translateY(-1px);
}
.btn-extension svg,
.btn-extension img {
    flex-shrink: 0;
}