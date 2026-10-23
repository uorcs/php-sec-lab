# PHP-FPM Process Failure and HTTPS Demonstration

This lab demonstrates how Nginx and PHP-FPM behave when a PHP script encounters a memory-limit error or terminates its own process unexpectedly.

It uses Docker Compose to run a PHP-FPM backend behind an Nginx web server configured for HTTPS. By comparing the responses and logs from two test scripts, the lab illustrates the difference between application-level errors and backend process failures.

**Note:** This lab was originally built and tested on virtual machines; this is the containerised version. It is a controlled process-failure demonstration, not a reproduction or exploit of a specific CVE.

## Lab Architecture

The environment consists of two containers:

- **PHP-FPM:** Executes the PHP scripts.
- **Nginx:** Terminates HTTPS connections, forwards PHP requests to PHP-FPM, and returns responses to the client.

## Directory Structure

Ensure your project contains the following files:

```text
php-sec-lab/
├── docker-compose.yml
├── nginx.conf
├── managed.php
├── unmanaged.php
├── ssl/
│   ├── nginx.crt
│   └── nginx.key
└── php-config/
    └── custom-limits.ini
```

## How to Run the Lab

Start the containers:

```bash
docker compose up -d
```

Monitor the logs in a separate terminal:

```bash
docker compose logs -f
```

Send test requests from another terminal:

```bash
# Test 1: PHP memory-limit exhaustion
curl -k -i https://127.0.0.1/managed.php

# Test 2: Intentional process termination
curl -k -i https://127.0.0.1/unmanaged.php
```

The `-k` option allows curl to accept the lab's certificate without validating its trust chain. It is intended for local testing, not normal production use.

## Understanding the Results

### Test 1: `managed.php`

This script repeatedly allocates memory until PHP reaches its configured memory limit.

Depending on the PHP configuration and error-handling behaviour, PHP may report a fatal memory-allocation error. The resulting HTTP status depends on whether a response has already been sent and how the failure is handled.

### Test 2: `unmanaged.php`

This script uses `posix_kill()` to send signal 11 (`SIGSEGV`) to its own process.

If the PHP-FPM worker terminates before completing the FastCGI response, Nginx may return an HTTP `502 Bad Gateway` error. PHP-FPM may also start a replacement worker.

Look for log messages indicating:

- A PHP-FPM worker terminated with signal 11.
- Nginx returned a 502 response for `unmanaged.php`.
- PHP-FPM started a replacement worker.
- The request to `managed.php` completed or returned a PHP error.

Exact log messages, worker identifiers, response sizes, and HTTP statuses may vary.

## What Does HTTPS Protect?

HTTPS uses TLS to protect data in transit between the client and the web server. In this configuration, Nginx terminates the TLS connection and forwards PHP requests to the backend over the Docker network.

HTTPS does not prevent a PHP script from exhausting its memory limit or terminating a worker process.

This lab illustrates the distinction between transport security and application reliability. Secure communications must be complemented by appropriate application error handling, resource limits, process isolation, and monitoring.

## Frequently Asked Questions

### Does this lab reproduce a PHP vulnerability?

No. The lab demonstrates selected failure behaviours using controlled PHP scripts. In particular, `unmanaged.php` deliberately terminates its own process. This does not establish that the behaviour is caused by a specific PHP vulnerability.

### Would the same failure occur on an updated PHP version?

Yes. Because the script explicitly requests a low-level process termination via the operating system, updating the PHP engine version will not prevent this behavior. A patched version will still exhibit a worker failure and an Nginx 502 response when this file is called.

This differs from reproducing a real vulnerability, where a software patch would natively alter how the runtime handles the underlying defect.

### Does the custom PHP configuration apply to the container?

Yes, provided the configuration directory is mapped inside the container space. The `docker-compose.yml` file is configured to map the local `php-config/custom-limits.ini` file straight into PHP's configuration directory at startup. Note that `managed.php` explicitly sets its own local script limit to `16M`, which dictates its unique execution boundary.

## Acknowledgments

- **Lab design and architecture:** Badis
- **Technical assistance:** Developed with AI-assisted engineering support.
