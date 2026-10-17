<?php
declare(strict_types=1);

class Logger
{
    private static string $logFile = '';

    private static function getLogFile(): string
    {
        if (!self::$logFile) {
            self::$logFile = ROOT_PATH . '/database/app.log';
        }
        return self::$logFile;
    }

    private static function write(string $level, string $action, string $message, array $ctx = []): void
    {
        $uid = $_SESSION['user_id'] ?? null;
        $ip  = $_SERVER['REMOTE_ADDR'] ?? null;

        // Write to DB
        try {
            $db = Database::getInstance();
            $db->prepare("INSERT INTO system_logs (level,action,message,context,user_id,ip_address) VALUES (?,?,?,?,?,?)")
               ->execute([
                   $level, $action, $message,
                   $ctx ? json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                   $uid, $ip,
               ]);
        } catch (Throwable) {}

        // Also write to rotating log file for critical/error
        if (in_array($level, ['error','critical'])) {
            $line = sprintf("[%s][%s][%s] %s %s\n",
                date('Y-m-d H:i:s'), strtoupper($level), $action, $message,
                $ctx ? json_encode($ctx) : '');
            @file_put_contents(self::getLogFile(), $line, FILE_APPEND | LOCK_EX);
        }
    }

    public static function debug(string $a, string $m, array $c=[]): void    { self::write('debug',   $a,$m,$c); }
    public static function info(string $a, string $m, array $c=[]): void     { self::write('info',    $a,$m,$c); }
    public static function warning(string $a, string $m, array $c=[]): void  { self::write('warning', $a,$m,$c); }
    public static function error(string $a, string $m, array $c=[]): void    { self::write('error',   $a,$m,$c); }
    public static function critical(string $a, string $m, array $c=[]): void { self::write('critical',$a,$m,$c); }

    public static function cleanup(int $days = 90): void
    {
        try {
            Database::getInstance()
                ->prepare("DELETE FROM system_logs WHERE datetime(created_at) < datetime('now',?)")
                ->execute(["-{$days} days"]);
        } catch (Throwable) {}
    }
}
