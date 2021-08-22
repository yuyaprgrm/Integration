<?php

namespace integration;

use Exception;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use raklib\protocol\Packet;

class Main extends PluginBase
{

    private static Main $instance;

    public static function getInstance(): Main
    {
        return Main::$instance;
    }


    /** System */
    const CONFIG_VERSION = 2;

    private AsyncGetMessageTask $asyncTask;

    /** @var resource|bool $webSocketServerProc */
    private $webSocketServerProc;

    private string $uuid;

    public function onLoad()
    {
        $this->saveDefaultConfig();
        
        $identification = new Config($this->getDataFolder().'identification.yml', Config::YAML);

        if ($identification->get('uuid', null) == null) {
            $identification->set('uuid', UUID::fromRandom()->toString());
            $identification->save();
        }

        $this->uuid = $identification->get('uuid');

        if (!$this->isConfigLatestVersion()) {
            $this->updateConfigFile();
            $this->getLogger()->info('設定ファイルをアップデートしました');
        }

        if ($this->getConfig()->get('use-internal-websocket-server')) {
            try {
                $port = $this->getConfig()->get('port');
                $this->startInternalWebsocketServer($port);
                $this->getLogger()->info(TextFormat::AQUA."内部サーバーの起動完了:ポート${port}");
            } catch (\Exception $ex) {
                $this->getLogger()->warning("内部サーバーの起動に失敗しました");
            }
        }

        Main::$instance = $this;
    }

    public function onEnable()
    {
        $host = $this->getConfig()->get('host');
        $port = $this->getConfig()->get('port');

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->asyncTask = new AsyncGetMessageTask($host, $port);
        $this->getServer()->getAsyncPool()->submitTask($this->asyncTask);
        $this->postMessage("鯖", "§6§lオンラインになりました");
    }

    public function postMessage(string $name, string $message): void
    {
        $config = $this->getConfig();
        $host = $config->get('host');
        $port = $config->get('port');
        $uuid = $this->uuid;
        $servername = $config->get('server-name', null);
        $props = [
            'player-name' => $name,
            'message' => $message,
            'uuid' => $uuid,
            'server-name' => mb_substr($servername ?? $this->getServer()->getName(), 0, 10)
        ];

        $this->getServer()->getAsyncPool()->submitTask(new AsyncPostMessageTask($host, $port, $props));
    }

    /**
     * @param array<string, string> $props
     */
    public function broadcastMessage(array $props): void
    {
        if (!isset($props['uuid']) or $props['uuid'] == $this->uuid) return;

        if (!isset($props['player-name']) or !isset($props['message'])) return;

        $serverName = $props['server-name'] ?? 'unknown';
        $this->getServer()->broadcastMessage("[{$serverName}]{$props['player-name']}: {$props['message']}");
    }

    public function onDisable()
    {
        $this->saveConfig();
        $this->asyncTask->close();

        if ($this->getConfig()->get('use-internal-websocket-server') && 
            is_resource($this->webSocketServerProc)) {
            proc_terminate($this->webSocketServerProc);
        }
    }

    private function isConfigLatestVersion(): bool
    {
        return ($this->getConfig()->get('version', 0) == Main::CONFIG_VERSION);
    }

    private function updateConfigFile(): void
    {
        $config_array = $this->getConfig()->getAll();

        var_dump($this->saveResource('config.yml', true));
        $this->reloadConfig();

        foreach ($config_array as $key => $value) {
            $k = (string) $key;
            if ($k!='version' && $this->getConfig()->exists($k)) {
                $this->getConfig()->set($k, $value);
            }
        }

        $this->saveConfig();
    }

    private function startInternalWebsocketServer(int $port): void
    {
        $this->webSocketServerProc = proc_open("php ./websocket/Boot.php ${port}", [], $pipes, __DIR__);

        if (!is_resource($this->webSocketServerProc)) {
            throw new \Exception('内部サーバー起動失敗');
        }
    }
}
