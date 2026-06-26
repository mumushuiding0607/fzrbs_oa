<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    // 正式环境
    // 'apiPrefix' => 'http://129.0.99.30:8030/',
    // 测试环境
    // 'apiPrefix' => 'http://129.0.98.30:8023/',
    // 本地环境
    'apiPrefix' => 'http://129.0.99.30:8030/',
    'webDomain' => 'https://fzrb.fznews.com.cn/',
    'excludeDomain' => ['129.0.98.30', 'fzrb.fznews.com.cn', '129.0.99.30'],
];
