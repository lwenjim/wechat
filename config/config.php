<?php

return [
    'TaskIntervalPointer' => 18,
    'PreMaxReadArticleTimes' => 3,
    'NextMaxReadArticleTimes' => 3,
    'InviteUserGainCoin' => 60,
    //注意：强烈建议开发组新同事，新建自己的组然后新增key，避免冲突错乱key的顺序
    'RedisKey' => [
        'mornight:platform:totalUser',
        'mornight:platform:total_sign',
        'mornight:platform:total_user',
        'mornight:oauth:Correlation:failUserId',
    ],
];