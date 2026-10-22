# Application Memory Isolation vs. Network Encryption
## CVE-2026-109618 (PHP2Shell) demonstration

**Author:** Badis
**Dafted:** 21 October 2026
**Last update:** 22 October 2026
**Course:** Systems and Network Security

This lab demonstrates the architectural boundary between network-layer encryption (HTTPS) and application-layer process safety. By observing a managed runtime exception alongside an unmanaged process failure, students will analyse how memory corruption vulnerabilities bypass security controls and map directly to critical real-world exploits.

*Note: this lab was built and tested on virtual machines first, this is the containrised version.*

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
   curl -vk https://127.0.0/managed.php

   # Test 2: Trigger the Unmanaged Process Collapse
   curl -vk https://127.0.0/unmanaged.php
   ```

## Critical Telemetry Lines to Identify

Students must locate and explain the following four indicators in their lab reports:

1. **`WARNING: [pool www] child 7 exited on signal 11 (SIGSEGV - core dumped)`**
   * **What it means:** A Segmentation Fault occurred. The backend application committed an unmanaged memory access violation, forcing the Linux kernel to terminate the execution thread immediately. The raw, uncleared contents of the process RAM were dumped to disk.

2. **`"GET /unmanaged.php HTTP/1.1" 502 157`**
   * **What it means:** Nginx returned an HTTP 502 Bad Gateway. Because the backend PHP container died instantly, the reverse proxy was left stranded mid-negotiation and could not read a valid response header.

3. **`NOTICE: [pool www] child 9 started`**
   * **What it means:** Automated service healing. The master process manager detected the structural death of the active worker and immediately initialised a new thread to maintain availability.

4. **`"GET /managed.php HTTP/1.1" 200 181`**
   * **What it means:** A managed software exception. PHP's internal allocator caught the script memory ceiling safely, allowing Nginx to return a healthy HTTP 200 OK along with a standard text-based error message.

## Why This Matters (The SSL/TLS Paradigm)

A common misconception is that implementing SSL/TLS certificates and HTTPS renders a web application entirely secure. This lab disproves that assumption through the following principles:

* **Encryption is Transitory:** SSL/TLS only protects data while it is in transit across the wire, blinding network packet sniffers (like Wireshark or tcpdump).
* **The Decryption Point:** The moment an encrypted packet arrives at the server, Nginx decrypts it back into raw text data before forwarding it to the backend application handler.
* **The Application Layer Exposure:** If the backend software processing the data contains a memory management flaw, an unauthenticated attacker can spray malicious input parameters through the encrypted line. Once decrypted, those parameters cause an unmanaged process failure (SIGSEGV).
* **The Security Lesson:** True system engineering requires hardening every layer of the stack. A secure network pipe cannot protect against structural code vulnerabilities running inside the volatile memory of the host.

## Mapping to the October 21, 2026 PHP Security Crisis

The unmanaged process collapse simulated in this lab replicates the **crashing behavior and logging footprints of the first critical vulnerability** patched in the PHP security release on **October 21, 2026** [21 Oct 2026]. While this lab focuses strictly on observing process termination telemetry rather than weaponised exploitation, understanding these crash states is a prerequisite to analysing real-world memory flaws [21 Oct 2026].

### 1. The OOM Request Parser Defect: CVE-2026-109618 (PHP2Shell)
This vulnerability directly inspires the unmanaged process simulation analysed in this lab assignment [21 Oct 2026].
* **The Vulnerability Mechanic:** When an unauthenticated multi-part request forces a worker thread to cross its internal memory allocation ceiling, PHP triggers an abrupt emergency stop routine (`longjmp()`) [21 Oct 2026].
* **The Log Footprint:** This sudden abort triggers the exact **`SIGSEGV (Signal 11)`** crash and **`502 Bad Gateway`** response that students observe when querying `unmanaged.php` [21 Oct 2026].
* **The Theoretical RCE Path:** In a real exploitation scenario (which goes beyond the scope of this lab), an attacker uses "Heap Spraying" to flood the volatile RAM with malicious code strings right before triggering this crash [21 Oct 2026]. Because the process terminates unmanaged without erasing its memory, those raw payload bytes remain floating intact inside uninitialised memory addresses [21 Oct 2026]. When a subsequent request recycles that dirty memory block, the engine accidentally executes the attacker's lingering data, granting unauthorised code execution [21 Oct 2026].

### 2. The Stale Request Globals Defect: CVE-2026-109619 (Not Demonstrated)
This represents the second critical flaw resolved in the same security patch release, carrying a parallel **CVSS rating of 9.2** [21 Oct 2026].
* **The Vulnerability Mechanic:** Unlike the OOM crash simulated in this lab, this is a **Use-After-Free** memory corruption bug [21 Oct 2026]. It occurs after a web transaction completely concludes, where internal tracking markers (like upload progress parameters) fail to wipe cleanly, allowing request-owned properties to survive as "stale state" inside the worker cache [21 Oct 2026].
* **Note on Lab Scope:** This mechanic is **not simulated** by our environment. It requires complex timing states to hijack memory pointers across different connection cycles before normal initialization routines can reset them [21 Oct 2026]. It is included here for theoretical context regarding the scope of the October 2026 patch cycle [21 Oct 2026].


## Acknowledgments
* **Lab Design & Architecture:** Badis
* **Technical Engineering Collaboration:** Developed in partnership with Google AI assistants (October 2026).
