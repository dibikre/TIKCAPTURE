name: uploader
creator_pic: thumbnails[n].url (varier n pour avoir une image disponible)
status_live: live_status (si valeur est is_live c'est que en live sinon....)
room_id: subtitles.live_chat[0].video_id
live_title: fulltitle (a decoder car parfois unicode)
live_description: description (a decoder car parfois unicode)
follower: channel_follower_count
followed:  xxxxxxxx
spectator: view_count
live_since: release_timestamp
origine_quality: formats[n].url (c'est un m3u8 du format n choisi ici formats[0].resolution)