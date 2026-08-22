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

        /* Header */
        .header {
            background: rgba(30, 30, 30, 0.5);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            overflow: visible;
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
            animation: animStarRotate 180s linear infinite;
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

        .b-menu::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 120%;
            height: 100%;
            background: #66ff66;
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
            z-index: 999;
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

        /* Animations étoiles */
        @keyframes animStar {
            from { transform: translateY(0); }
            to { transform: translateY(-135rem); }
        }

        @keyframes animStarRotate {
            from { transform: rotate(360deg); }
            to { transform: rotate(0); }
        }

        /* Responsive Header */
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
                order: 3;
                flex-shrink: 0;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 0.75rem 1rem;
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
        }
        
        .content-block.single-column {
            grid-template-columns: 1fr;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
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

        .search-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
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
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.25rem;
        }

        .search-input {
            width: 100%;
            padding: 1.25rem 1.25rem 1.25rem 3.5rem;
            background: var(--bg-tertiary);
            border: 2px solid var(--border-color);
            border-radius: 14px;
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
            padding: 1.25rem 2rem;
            border: none;
            border-radius: 14px;
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

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #00e676 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 40px rgba(0, 200, 83, 0.3);
        }

        .btn-info {
            background: linear-gradient(135deg, var(--accent-secondary) 0%, #00bcd4 100%);
            color: #121212;
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 40px rgba(37, 244, 238, 0.3);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ff9800 0%, #ffc107 100%);
            color: #121212;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 40px rgba(255, 193, 7, 0.3);
        }

        /* Results Section */
        .results-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .results-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Video Card */
        .video-card {
            background: var(--bg-card);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .video-header {
            display: flex;
            gap: 1.5rem;
            padding: 2rem;
        }

        .video-thumbnail {
            width: 200px;
            height: 200px;
            border-radius: 16px;
            object-fit: cover;
            border: 3px solid var(--border-color);
            flex-shrink: 0;
        }

        .video-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .video-author {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .author-badge {
            background: var(--accent-gradient);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .video-username {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .video-date {
            color: var(--text-secondary);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Download Actions */
        .download-actions {
            padding: 0 2rem 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .download-actions .btn {
            justify-content: center;
            padding: 1rem 1.5rem;
            width: 100%;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-info {
            background: rgba(37, 244, 238, 0.1);
            border: 1px solid rgba(37, 244, 238, 0.3);
            color: var(--accent-secondary);
        }

        .alert-danger {
            background: rgba(255, 82, 82, 0.1);
            border: 1px solid rgba(255, 82, 82, 0.3);
            color: var(--error);
        }

        /* Features Section */
        .features-section {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2rem;
            border: 1px solid var(--border-color);
        }

        .features-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--text-primary);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .feature-item {
            text-align: center;
            padding: 1.5rem;
            background: var(--bg-tertiary);
            border-radius: 16px;
            transition: transform 0.3s ease;
        }

        .feature-item:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .feature-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .feature-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Download Options Cards */
        .options-section {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2rem;
            border: 1px solid var(--border-color);
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .option-card {
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .option-card:hover {
            border-color: var(--accent-primary);
        }

        .option-card.recommended {
            border-color: var(--success);
            position: relative;
        }

        .option-card.recommended::before {
            content: '✨ Recommandé';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--success);
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .option-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .option-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .option-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .option-features {
            text-align: left;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .option-features li {
            margin-bottom: 0.5rem;
            list-style: none;
        }

        /* Footer */
        .footer {
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            padding: 1.5rem 2rem;
            text-align: center;
            margin-top: 3rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .footer a {
            color: var(--accent-secondary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: var(--accent-primary);
        }

        /* Last Modified Badge */
        .last-modified {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--bg-tertiary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 1.5rem;
        }

        .last-modified a {
            color: var(--accent-secondary);
            text-decoration: none;
        }

        .last-modified a:hover {
            text-decoration: underline;
        }

        /* Loading Spinner */
        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Spinner bouton recherche */
        .btn-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            flex-shrink: 0;
        }
        
        .btn-primary.loading .btn-spinner {
            display: inline-block;
        }
        
        .btn-primary.loading .btn-icon {
            display: none;
        }

        /* Statistiques auteur */
        .author-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--accent-primary);
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .video-signature {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Détails vidéo */
        .video-details {
            padding: 0 2rem 2rem;
        }

        .video-description,
        .video-hashtags,
        .quality-options {
            margin-bottom: 2rem;
        }

        .video-description h3,
        .video-hashtags h3,
        .quality-options h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .video-description p {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .hashtag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .hashtag {
            background: var(--bg-tertiary);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--accent-secondary);
        }

        /* Grille de statistiques */
        .video-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-tertiary);
            padding: 1.5rem;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.5rem;
        }

        .stat-icon {
            font-size: 2rem;
        }

        .stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--accent-primary);
        }

        .stat-text {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* Qualités vidéo */
        .quality-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .quality-card {
            background: var(--bg-tertiary);
            padding: 1rem;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .quality-card:hover {
            border-color: var(--accent-primary);
        }

        .quality-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .quality-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .btn-sm {
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
        }

        .btn-quality {
            width: 100%;
            background: linear-gradient(135deg, var(--accent-primary), #ff6b6b);
        }

        .btn-subtitle {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            text-decoration: none;
        }

        .subtitle-section {
            margin-top: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .search-section {
                padding: 1.5rem;
            }

            .search-form {
                flex-direction: column;
            }

            .video-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .video-thumbnail {
                width: 180px;
                height: 180px;
            }

            .download-actions {
                grid-template-columns: 1fr;
            }

            .footer-content {
                flex-direction: column;
                gap: 0.5rem;
            }

            .header-status {
                display: none;
            }
        }

        .related-videos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .related-video-card {
            background: var(--bg-tertiary);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            border: 2px solid var(--border-color);
            cursor: pointer;
        }

        .related-video-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-primary);
            box-shadow: var(--shadow-glow);
        }

        .related-video-thumbnail {
            position: relative;
            width: 100%;
            padding-top: 177.78%; /* Ratio 9:16 pour TikTok */
            overflow: hidden;
        }

        .related-video-thumbnail img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .related-video-duration {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .related-video-info {
            padding: 1rem;
        }

        .related-author {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .related-author-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .related-author-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .related-video-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .related-video-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .related-videos-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .related-videos-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ============================================================
        CONTENT SECTION STYLES (à ajouter dans le CSS existant)
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

        /* Features Grid - Mise à jour pour éviter les conflits */
        .content-section .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
            grid-column: 1 / -1;
        }

        .content-section .feature-card {
            padding: 2rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        .content-section .feature-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-5px);
            box-shadow: var(--shadow-glow);
        }

        .content-section .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: block;
        }

        .content-section .feature-card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .content-section .feature-card p {
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

        /* Tips Grid */
        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
            grid-column: 1 / -1;
        }

        .tip-card {
            padding: 1.5rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .tip-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-3px);
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
            margin-bottom: 1rem;
            width: 10%;
        }

        .tip-card h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .tip-card p {
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

            .content-section .features-grid,
            .use-cases,
            .technical-grid,
            .faq-grid,
            .tips-grid {
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
            text-decoration: none;
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
            color: var(--text-muted);
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