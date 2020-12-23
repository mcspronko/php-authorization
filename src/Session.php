<?php

declare(strict_types=1);

namespace App;

class Session
{
    public function start(): void
    {
        session_start();
    }

    /**
     * @param string $key
     * @param $value
     */
    public function setData(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getData(string $key)
    {
        return !empty($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public function save(): void
    {
        session_write_close();
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function flush(string $key)
    {
        $value = $this->getData($key);
        $this->unset($key);

        return $value;
    }

    /**
     * @param string $key
     */
    private function unset(string $key): void
    {
        unset($_SESSION[$key]);
    }
}
