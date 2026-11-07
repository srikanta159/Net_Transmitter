# Net_Transmitter

[![CI](https://github.com/srikanta159/Net_Transmitter/actions/workflows/ci.yml/badge.svg)](https://github.com/srikanta159/Net_Transmitter/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%20|%208.0%20--%208.4-8892BF.svg)](https://www.php.net/)
[![License: LGPL v2.1](https://img.shields.io/badge/License-LGPL_v2.1-blue.svg)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/srikanta159/net_transmitter.svg)](https://packagist.org/packages/srikanta159/net_transmitter)

A robust, object-oriented stream wrapper that **guarantees data integrity** for network streams, TCP sockets, and local stream resources in PHP.

---

## The Problem

PHP provides native support for various stream types, including TCP/IP sockets, Unix domain sockets, and HTTP streams. However, standard I/O functions like `fread()` and `fwrite()` **do not guarantee** that all intended data is read or written in a single call. Factors such as network latency, packet fragmentation, kernel buffer saturation, and non-blocking I/O frequently lead to partial reads and writes.

Writing boilerplate loops with custom byte counters, timers, and error handlers for every single socket transmission is tedious and error-prone.

## The Solution

**Net_Transmitter** abstracts this complexity away. When you request to send or receive $N$ bytes, Net_Transmitter ensures that your application interacts with exactly $N$ bytes—reliably chunked, buffered, and verified.

---

## Key Features

- **Guaranteed Byte Integrity**: Handles partial socket reads and writes automatically until the exact byte count has been transferred or an explicit timeout occurs.
- **Modern PHP Compatibility**: Fully modernized and tested for **PHP 7.4, 8.0, 8.1, 8.2, 8.3, and 8.4**.
- **Memory-Efficient Chunking**: Send and receive gigabytes of data with bounded memory consumption using configurable chunk sizes (default `1 MB`).
- **Persistent Connection Locking**: Integrated mutual exclusion locking via shared memory (`PEAR2\Cache\SHM` / APCu) to prevent race conditions on reused persistent sockets.
- **TLS / SSL Cryptography**: Flexible runtime encryption supporting TLS 1.0, 1.1, 1.2, 1.3 and automatic version negotiation.
- **Stream Filter Pipelines**: Apply dynamic stream filters (`FilterCollection`) during data receipt without corrupting the underlying socket state.
- **TCP Client & Server Support**: Dedicated `TcpClient` and `TcpServerConnection` classes for client and daemon socket programming.
- **Detailed Error Recovery**: When exceptions occur, inspect `$e->getFragment()` to retrieve the exact bytes transferred before the failure.

---

## Installation

Install via Composer:

```bash
composer require srikanta159/net_transmitter
```

---

## Quick Start

### 1. Basic TCP Client

Connect to a remote service, transmit a payload, and receive an exact response:

```php
use PEAR2\Net\Transmitter\TcpClient;
use PEAR2\Net\Transmitter\SocketException;

try {
    // Connect to hostname and port with a 5-second timeout
    $client = new TcpClient('example.com', 80, false, 5);

    // Send HTTP request
    $request = "GET / HTTP/1.1\r\nHost: example.com\r\nConnection: close\r\n\r\n";
    $bytesSent = $client->send($request);

    // Wait until response data is available (up to 3 seconds)
    if ($client->isDataAwaiting(3)) {
        // Read exactly 128 bytes (e.g., HTTP header fragment)
        $headerFragment = $client->receive(128);
        echo $headerFragment;
    }

    $client->close();
} catch (SocketException $e) {
    echo "Socket error: " . $e->getMessage() . "\n";
    echo "Bytes transferred before failure: " . strlen($e->getFragment()) . "\n";
}
```

### 2. Streaming Large Files (Memory-Efficient)

Net_Transmitter can stream file resources directly over a socket without loading the whole file into PHP memory:

```php
use PEAR2\Net\Transmitter\TcpClient;

$client = new TcpClient('storage.internal', 9000);

// Open a large file resource
$fileStream = fopen('/var/data/large-archive.tar.gz', 'rb');

// Stream from current position with a custom 256 KB chunk size
$client->setChunk(256 * 1024);
$totalSent = $client->send($fileStream);

fclose($fileStream);
$client->close();
```

### 3. Receiving Data Directly into a Stream with Filters

Receive incoming data into a temporary stream while applying on-the-fly transformations:

```php
use PEAR2\Net\Transmitter\TcpClient;
use PEAR2\Net\Transmitter\FilterCollection;

$client = new TcpClient('127.0.0.1', 8080);

// Set up stream filter pipeline (e.g., base64 decode)
$filters = new FilterCollection();
$filters->append('convert.base64-decode');

// Receive 1024 bytes and write decoded output into a stream
$streamResource = $client->receiveStream(1024, $filters);

echo stream_get_contents($streamResource);
fclose($streamResource);
```

### 4. Encrypted TLS/SSL Connections

Initiate an encrypted connection directly or switch to TLS on an existing socket:

```php
use PEAR2\Net\Transmitter\TcpClient;
use PEAR2\Net\Transmitter\NetworkStream;

// Connect directly using TLS
$sslContext = stream_context_create([
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
    ]
]);

$client = new TcpClient(
    'secure.example.com',
    443,
    false,
    10,
    '',
    NetworkStream::CRYPTO_TLS,
    $sslContext
);

$client->send("HEAD / HTTP/1.1\r\nHost: secure.example.com\r\nConnection: close\r\n\r\n");
echo $client->receive(128);
```

### 5. TCP Socket Server Connection

Wrap server connections accepted via `stream_socket_accept`:

```php
use PEAR2\Net\Transmitter\TcpServerConnection;

$server = stream_socket_server('tcp://0.0.0.0:8080', $errno, $errstr);

while ($server) {
    // Wrap accepted client socket
    $connection = new TcpServerConnection($server);

    echo "Client connected from: {$connection->getPeerIP()}:{$connection->getPeerPort()}\n";

    // Receive 4-byte message length header
    $lengthHeader = $connection->receive(4);
    $payloadLength = unpack('Nlen', $lengthHeader)['len'];

    // Read full payload with guaranteed byte count
    $payload = $connection->receive($payloadLength);

    // Echo back confirmation
    $connection->send("OK\n");
    $connection->close();
}
```

### 6. Persistent Connections with Concurrency Locks

When sharing sockets across multiple requests with persistent connections, Net_Transmitter prevents concurrent read/write corruption using shared memory:

```php
use PEAR2\Net\Transmitter\TcpClient;
use PEAR2\Net\Transmitter\Stream;

// 3rd argument = true enables persistent socket, 5th argument = unique persistent key
$client = new TcpClient('127.0.0.1', 6379, true, 2.0, 'redis_worker_pool');

// Acquire exclusive sending lock for transaction sequence
$previousLockState = $client->lock(Stream::DIRECTION_SEND);

$client->send("*1\r\n\$4\r\nPING\r\n");

// Restore previous lock state
$client->lock($previousLockState, true);
```

---

## Class Architecture

| Class | Description |
|---|---|
| [`Stream`](src/PEAR2/Net/Transmitter/Stream.php) | Base wrapper for any PHP stream resource. Provides chunking, buffer control, non-blocking polling, and guaranteed `send()` / `receive()`. |
| [`NetworkStream`](src/PEAR2/Net/Transmitter/NetworkStream.php) | Extends `Stream` for network-specific protocols with TLS/SSL negotiation and half-duplex shutdown. |
| [`TcpClient`](src/PEAR2/Net/Transmitter/TcpClient.php) | Convenience client for TCP connections, persistent connection pooling, and SHM locking. |
| [`TcpServerConnection`](src/PEAR2/Net/Transmitter/TcpServerConnection.php) | Wrapper for incoming server connections accepted by `stream_socket_server()`. |
| [`FilterCollection`](src/PEAR2/Net/Transmitter/FilterCollection.php) | Seekable iterator representing an ordered pipeline of stream filters. |
| [`StreamException`](src/PEAR2/Net/Transmitter/StreamException.php) | Base runtime exception providing access to `$e->getFragment()`. |
| [`SocketException`](src/PEAR2/Net/Transmitter/SocketException.php) | Specialized exception exposing socket error numbers and system messages. |
| [`LockException`](src/PEAR2/Net/Transmitter/LockException.php) | Thrown when shared-memory locking fails. |

---

## Running Tests

Run the test suite using PHPUnit:

```bash
composer test
```

Or run directly:

```bash
vendor/bin/phpunit
```

---

## Continuous Integration

Every push and pull request is automatically tested across a matrix of PHP versions (**7.4, 8.0, 8.1, 8.2, 8.3, and 8.4**) via [GitHub Actions](.github/workflows/ci.yml).

---

## Authors & Credits

- **Srikanta** ([@srikanta159](https://github.com/srikanta159)) – Modernization, PHP 8.x compatibility, GitHub Actions CI, and maintenance.
- **Vasil Rangelov** ([boen.robot@gmail.com](mailto:boen.robot@gmail.com)) – Original concept and architecture.

---

## License

This package is open-source software licensed under the **GNU Lesser General Public License version 2.1 (LGPL-2.1)**. See the [LICENSE](LICENSE) file for details.
