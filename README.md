# Lab Guide: Application Memory Isolation vs. Network Encryption

This lab demonstrates the architectural boundary between network-layer encryption (HTTPS) and application-layer process safety. By observing a managed runtime exception alongside an unmanaged process failure, students will analyze how memory corruption vulnerabilities bypass security controls.

## Directory Structure
Ensure your local project folder contains only the following assets:
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

1. Start the container infrastructure in the background:
   ```bash
   sudo docker compose up -d
   ```

2. Attach a live terminal monitor to trail the system log telemetry:
   ```bash
   sudo docker compose logs -f
   ```

3. Execute the verification requests from a secondary terminal window:
   ```bash
   # Test 1: Trigger the Handled Runtime Exception
   curl -vk https://127.0.0

   # Test 2: Trigger the Unmanaged Process Collapse
   curl -vk https://127.0.0
   ```

## Critical Telemetry Lines to Identify

Students must locate and explain the following four indicators in their lab reports:

1. **`WARNING: [pool www] child 7 exited on signal 11 (SIGSEGV - core dumped)`**
   * **What it means:** A Segmentation Fault occurred. The backend application committed an unmanaged memory access violation, forcing the Linux kernel to terminate the execution thread immediately. The raw, uncleared contents of the process RAM were dumped to disk.

2. **`"GET /unmanaged.php HTTP/1.1" 502 157`**
   * **What it means:** Nginx returned an HTTP 502 Bad Gateway. Because the backend PHP container died instantly, the reverse proxy was left stranded mid-negotiation and could not read a valid response header.

3. **`NOTICE: [pool www] child 9 started`**
   * **What it means:** Automated service healing. The master process manager detected the structural death of the active worker and immediately initialized a new thread to maintain availability.

4. **`"GET /managed.php HTTP/1.1" 200 181`**
   * **What it means:** A managed software exception. PHP's internal allocator caught the script memory ceiling safely, allowing Nginx to return a healthy HTTP 200 OK along with a standard text-based error message.

## Why This Matters (The SSL/TLS Paradigm)

A common misconception is that implementing SSL/TLS certificates and HTTPS renders a web application entirely secure. This lab disproves that assumption through the following principles:

* **Encryption is Transitory:** SSL/TLS only protects data while it is in transit across the wire, blinding network packet sniffers (like Wireshark or tcpdump).
* **The Decryption Point:** The moment an encrypted packet arrives at the server, Nginx decrypts it back into raw text data before forwarding it to the backend application handler.
* **The Application Layer Exposure:** If the backend software processing the data contains a memory management flaw, an unauthenticated attacker can spray malicious input parameters through the encrypted line. Once decrypted, those parameters cause an unmanaged process failure (SIGSEGV).
* **The Security Lesson:** True system engineering requires hardening every layer of the stack. A secure network pipe cannot protect against structural code vulnerabilities running inside the volatile memory of the host.
