<?php
declare(strict_types=1);
class AuthMiddleware { public static function handle(): ?array { return auth(); } }
