<?php
namespace App\Core;

class StateManager
{
    private string $path;

    public function __construct(string $path = 'storage/state.json')
    {
        $this->path = $path;
        if (!file_exists($path)) {
            file_put_contents($path, json_encode([]));
        }
    }

    public function getLastCommand(int $chatId): ?string
    {
        $data = json_decode(file_get_contents($this->path), true);
        return $data[$chatId]['last_command'] ?? null;
    }

    public function setLastCommand(int $chatId, string $command): void
    {
        $data = json_decode(file_get_contents($this->path), true);
        $data[$chatId]['last_command'] = $command;
        file_put_contents($this->path, json_encode($data, JSON_PRETTY_PRINT));
    }
}
