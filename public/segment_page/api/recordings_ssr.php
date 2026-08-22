<?php
// /segment_page/api/recordings_ssr.php

function getRecentRecordingsHTML($baseUrl) {
    $storageFile = __DIR__ . '/../data/recordings.json';
    if (!file_exists($storageFile)) return '<!-- No recordings file -->';

    $content = file_get_contents($storageFile);
    $recordings = json_decode($content, true);
    if (!is_array($recordings) || empty($recordings)) return '<!-- Empty recordings -->';
    
    $recordings = array_slice($recordings, 0, 14);

    $html = '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 gap-3 mt-8">';
    
    foreach ($recordings as $rec) {
        $date = new DateTime($rec['recordedAt'] ?? 'now');
        $formattedDate = $date->format('d/m');
        $formattedTime = $date->format('H:i');
        
        $title = $rec['title'] ?: ($rec['nickname'] . " enregistré le " . $formattedDate . " à " . $formattedTime);
        $avatarUrl = $rec['avatar'];
        if (strpos($avatarUrl, '/') === 0) {
            $avatarUrl = $baseUrl . $avatarUrl;
        }

        $html .= '
        <div class="group flex flex-col text-left glass rounded-2xl overflow-hidden border border-white/10">
            <div class="relative aspect-square overflow-hidden bg-white/5 border-b border-white/8">
                <img src="'.$avatarUrl.'" class="w-full h-full object-cover" referrerPolicy="no-referrer" width="200" height="200" loading="lazy">
                
                <div class="absolute bottom-2 left-2">
                    <div class="flex items-center gap-1 bg-black/60 backdrop-blur-sm rounded-lg px-2 py-1">
                        <img src="'.$baseUrl.'/plateformes/tiktok.png" class="w-3.5 h-3.5 object-contain" width="14" height="14" loading="lazy">
                    </div>
                </div>

                <div class="absolute top-2 left-2">
                    <span class="bg-[#FF0050]/90 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full uppercase tracking-wide">Récent</span>
                </div>
            </div>
            <div class="p-3 flex-1 flex flex-col space-y-2">
                <div>
                    <p class="text-sm font-bold text-foreground truncate leading-tight">'.$rec['nickname'].'</p>
                    <p class="text-[11px] text-muted-foreground truncate">@'.$rec['uniqueId'].'</p>
                </div>

                <p class="text-[10px] text-muted leading-tight line-clamp-2 h-7 italic opacity-80">"'.$title.'"</p>

                <div class="grid grid-cols-2 gap-y-1.5 pt-2 border-t border-white/5">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="text-[10px] text-muted-foreground truncate">'.$formattedDate.'</span>
                    </div>
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="text-[10px] text-muted-foreground truncate">'.$formattedTime.'</span>
                    </div>
                </div>
            </div>
        </div>';
    }

    $html .= '</div>';
    return $html;
}
