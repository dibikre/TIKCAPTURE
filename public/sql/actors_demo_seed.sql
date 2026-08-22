INSERT INTO creators (id, name, profile_name, platform, avatar_url, cover_url, description) VALUES
('a1', 'Lena Rivers', '@lenarivers', 'TikTok', 'images/2026/03/lena-avatar.jpg', 'images/2026/03/lena-cover.jpg', 'Createur lifestyle et coulisses de tournage.'),
('a2', 'Noah Vibes', '@noahvibes', 'Twitch', 'images/2026/03/noah-avatar.jpg', 'images/2026/03/noah-cover.jpg', 'Streamer gaming et reaction.'),
('a3', 'Maya Spark', '@maya.spark', 'Kick', 'images/2026/03/maya-avatar.jpg', 'images/2026/03/maya-cover.jpg', 'Contenu creatif, musique et mini-vlogs.');

INSERT INTO videos (id, creator_id, title, thumbnail_url, video_url, duration, views, description, transcript) VALUES
('v1', 'a1', 'Routine matinale avant tournage', 'images/2026/03/v1-thumb.jpg', 'videos/2026/03/v1.mp4', '03:14', '123K', 'Moments forts de la routine matinale.', 'Bonjour a tous, voici ma routine matinale avant un tournage...'),
('v2', 'a1', '3 poses photo faciles', 'images/2026/03/v2-thumb.jpg', 'videos/2026/03/v2.mp4', '01:42', '95K', 'Trois poses simples et efficaces.', 'Dans cette video je montre 3 poses photo faciles...'),
('v3', 'a2', 'Top 5 clutch de la semaine', 'images/2026/03/v3-thumb.jpg', 'videos/2026/03/v3.mp4', '06:21', '152K', 'Compilation des meilleurs clutch.', 'Bienvenue dans le top 5 clutch de la semaine...'),
('v4', 'a2', 'React aux clips viewers', 'images/2026/03/v4-thumb.jpg', 'videos/2026/03/v4.mp4', '12:03', '190K', 'Reaction aux clips de la communaute.', 'Aujourd hui on react a vos meilleurs clips...'),
('v5', 'a3', 'Beatmaking en direct', 'images/2026/03/v5-thumb.jpg', 'videos/2026/03/v5.mp4', '11:09', '62K', 'Creation d un beat en live.', 'On commence par la rythmique puis on ajoute la melodie...'),
('v6', 'a3', 'Studio tour rapide', 'images/2026/03/v6-thumb.jpg', 'videos/2026/03/v6.mp4', '03:41', '49K', 'Presentation de mon setup studio.', 'Je vous montre mon micro, ma carte son et mes lumieres...');

INSERT INTO video_playback_tasks (video_id, timecode, label, position) VALUES
('v1', '00:00', 'Introduction', 1),
('v1', '00:25', 'Presentation des points cles', 2),
('v1', '01:10', 'Demonstration', 3),
('v1', '02:05', 'Conseils', 4),
('v1', '02:45', 'Conclusion', 5),
('v2', '00:00', 'Introduction', 1),
('v2', '00:20', 'Pose 1', 2),
('v2', '00:48', 'Pose 2', 3),
('v2', '01:10', 'Pose 3', 4),
('v3', '00:00', 'Intro top clutch', 1),
('v4', '00:00', 'Intro react clips', 1),
('v5', '00:00', 'Intro beatmaking', 1),
('v6', '00:00', 'Intro studio tour', 1);
