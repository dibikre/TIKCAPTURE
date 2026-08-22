name: a extraire et decoder de la balise suivant dans le code source de la page et se trouve apres le dernier pipe | <link rel="alternate" href="https://graph.facebook.com/v25.0/oembed_video?url=https%3A%2F%2Fwww.facebook.com%2FlThanMal%2Fvideos%2Fl%25C3%25B4ng-v%25C5%25A9-%25C4%2591%25C3%25A2y-r%25E1%25BB%2593i-li%25E1%25BB%2587u-c%25C3%25B3-th%25E1%25BB%2583-ra-wing-dk-hay-kh%25C3%25B4ng%2F2008118883391389%2F" title="L&#xf4;ng v&#x169; &#x111;&#xe2;y r&#x1ed3;i, li&#x1ec7;u c&#xf3; th&#x1ec3; ra wing DK hay kh&#xf4;ng | Th&#x1ea7;n Ma MU" type="application/json+oembed" />
creator_pic: require[0][3][0].__bbox.require[9][3][1].__bbox.result.data.video.story.attachments[0].media.preferred_thumbnail.image.uri (donnee a prendre du json VideoPlayerScrubberPreviewImag)
status_live: require[0][3][0].__bbox.require[9][3][1].__bbox.result.data.video.story.attachments[0].media.is_live_streaming (si true c'est que en live sinon false) (donnee a prendre du json VideoPlayerScrubberPreviewImag)
room_id: require[0][3][0].__bbox.require[9][3][1].__bbox.result.data.video.story.attachments[0].media.id (donnee a prendre du json VideoPlayerScrubberPreviewImag)
live_title: a extraire et decoder de la balise suivant dans le code source de la page <link rel="alternate" href="https://graph.facebook.com/v25.0/oembed_video?url=https%3A%2F%2Fwww.facebook.com%2FlThanMal%2Fvideos%2Fl%25C3%25B4ng-v%25C5%25A9-%25C4%2591%25C3%25A2y-r%25E1%25BB%2593i-li%25E1%25BB%2587u-c%25C3%25B3-th%25E1%25BB%2583-ra-wing-dk-hay-kh%25C3%25B4ng%2F2008118883391389%2F" title="L&#xf4;ng v&#x169; &#x111;&#xe2;y r&#x1ed3;i, li&#x1ec7;u c&#xf3; th&#x1ec3; ra wing DK hay kh&#xf4;ng | Th&#x1ea7;n Ma MU" type="application/json+oembed" />
live_description: a extraire et decoder de la balise suivant dans le code source de la page <meta name="description" content="L&#xf4;ng v&#x169; &#x111;&#xe2;y r&#x1ed3;i, li&#x1ec7;u c&#xf3; th&#x1ec3; ra wing DK hay kh&#xf4;ng" />
follower:
followed:
spectator: require[0][3][0].__bbox.require[9][3][1].__bbox.result.data.video.story.attachments[0].media.liveViewerCount (donnee a prendre du json VideoPlayerScrubberPreviewImag)
live_since: require[0][3][0].__bbox.require[9][3][1].__bbox.result.data.video.story.attachments[0].media.playable_duration_in_ms en millisecondes (donnee a prendre du json VideoPlayerScrubberPreviewImag)
origine_quality: 
qualities_list: 

laisser follower, followed, origine_quality et qualities_list vides pour le moment