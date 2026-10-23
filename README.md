## PHP Security Vulnerability - October 2026: A Simple Demonstration

### Why does this matter?

You may have noticed that Moodle was taken offline on 21 October 2026 following the exploitation of critical PHP vulnerabilities affecting Open LMS.

This lab introduces **CVE-2026-109618** and an important web security lesson: **HTTPS protects communications, but it does not make vulnerable software secure.**

### The vulnerability in simple terms

A website can use HTTPS and still be vulnerable to attacks against the software running behind it.

CVE-2026-109618 concerns PHP's handling of memory exhaustion during request processing. Under certain conditions, a memory-allocation failure can leave internal state improperly initialised. Subsequent operations may mishandle that state, resulting in memory corruption and potentially allowing remote code execution.

This is more serious than a simple application crash.

**Important:** This lab demonstrates related failure behaviours. It does not reproduce the specific memory-corruption mechanism or remote code execution associated with the CVE.

## Lab architecture

The lab runs two Docker containers:

- **PHP-FPM:** Executes the PHP scripts.
- **Nginx:** Handles HTTP/HTTPS requests and forwards PHP requests to PHP-FPM.

Nginx terminates HTTPS connections. Communication with PHP-FPM takes place over the internal Docker network.

### Files

```text
php-sec-lab/
├── docker-compose.yml
├── nginx.conf
├── managed.php
├── unmanaged.php
└── ssl/
    ├── nginx.crt
    └── nginx.key
```

### Run the lab

Start the containers:

```bash
docker compose up -d
```

Monitor the logs:

```bash
docker compose logs -f
```

In another terminal, run the tests individually.

**Test 1 — Memory exhaustion**

```bash
curl -k -i https://127.0.0.1/managed.php
```

The script repeatedly allocates memory until PHP reaches its configured memory limit of `16M`.

**Test 2 — PHP-FPM process termination**

```bash
curl -k -i https://127.0.0.1/unmanaged.php
```

The script sends signal 11 (`SIGSEGV`) to its own process, intentionally terminating the PHP-FPM worker.

The `-k` option allows curl to accept the lab certificate without validating its trust chain. Use it only for local testing.

### Understanding the results

#### Test 1: `managed.php`

PHP reaches its configured memory limit and encounters a memory-allocation failure. Observe the HTTP response and logs to see how the failure is reported.

#### Test 2: `unmanaged.php`

In testing, the logs confirmed the following sequence:

1. PHP-FPM worker 7 exited with signal 11 (`SIGSEGV`).
2. Nginx reported a connection reset while waiting for the upstream response.
3. Nginx returned HTTP `502 Bad Gateway`.
4. PHP-FPM started a replacement worker.

This demonstrates how the unexpected termination of a backend process can interrupt a request and produce a 502 response.

A `502` error alone does not prove that a security vulnerability has been exploited.

### Clean up
```
sudo docker compose down --remove-orphans
```

## What should you learn?

- **HTTPS** protects data in transit, not the software processing it.
- **Memory exhaustion** can cause application failures.
- **PHP-FPM process failure** can result in Nginx returning a 502 response.
- **Memory-corruption vulnerabilities** can have more serious consequences than a crash, potentially including remote code execution.

The key lesson is that secure communications must be complemented by secure, updated software and appropriate resource management and monitoring.

**Scope:** This is a controlled process-failure demonstration, not an exploit or reproduction of CVE-2026-109618. The lab was originally developed and tested on virtual machines and has since been containerised.

### Acknowledgments

- **Lab design and architecture:** Badis
- **Technical assistance:** Developed with AI-assisted engineering support.
