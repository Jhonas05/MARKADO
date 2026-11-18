<?php

namespace App\Reporting;

final class StoredZipWriter
{
    /** @var array<int, array{name: string, crc: int, size: int, offset: int}> */
    private array $entries = [];

    private string $body = '';

    public function add(string $name, string $contents): void
    {
        $offset = strlen($this->body);
        $crc = crc32($contents);
        $size = strlen($contents);
        [$time, $date] = $this->dosTime();
        $this->body .= pack('VvvvvvVVVvv', 0x04034B50, 20, 0, 0, $time, $date, $crc, $size, $size, strlen($name), 0);
        $this->body .= $name.$contents;
        $this->entries[] = compact('name', 'crc', 'size', 'offset') + compact('time', 'date');
    }

    public function finish(): string
    {
        $directoryOffset = strlen($this->body);
        $directory = '';
        foreach ($this->entries as $entry) {
            $directory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014B50,
                20,
                20,
                0,
                0,
                $entry['time'],
                $entry['date'],
                $entry['crc'],
                $entry['size'],
                $entry['size'],
                strlen($entry['name']),
                0,
                0,
                0,
                0,
                0,
                $entry['offset'],
            ).$entry['name'];
        }

        return $this->body.$directory.pack(
            'VvvvvVVv',
            0x06054B50,
            0,
            0,
            count($this->entries),
            count($this->entries),
            strlen($directory),
            $directoryOffset,
            0,
        );
    }

    private function dosTime(): array
    {
        $now = getdate();
        $time = ($now['hours'] << 11) | ($now['minutes'] << 5) | intdiv($now['seconds'], 2);
        $date = (($now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];

        return [$time, $date];
    }
}
