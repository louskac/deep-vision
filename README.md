# 🚀 High-Performance PHP POC (20k RPS / 300k OPS)

[cite_start]This Proof of Concept (POC) demonstrates a PHP 8.4+ architecture designed to meet extreme performance scaling requirements for DEEP VISION[cite: 5, 6]. [cite_start]The system is built to handle **~20,000 Requests Per Second (RPS)** and **~300,000 Operations Per Second (OPS)** with a dataset of **10+ million keys**.

---

## 🏗️ Architectural Decisions

To achieve the required throughput, this project deviates from standard synchronous stacks to leverage modern high-concurrency patterns:

* [cite_start]**Runtime:** **FrankenPHP (PHP 8.4)**[cite: 23]. [cite_start]By utilizing **Worker Mode**, the application stays resident in memory, eliminating the PHP bootstrap overhead per request—a necessity for hitting 20k RPS.
* **Data Store:** **DragonflyDB**. [cite_start]While the spec mentions Memcached/Redis, DragonflyDB is selected for its multi-threaded, shared-nothing architecture, which is essential for sustaining 300,000 OPS[cite: 12, 24].
* **Seeding:** **Rust-based CLI**. [cite_start]Ingesting 10M+ keys via PHP is inefficient for a POC of this scale. A custom Rust utility is used to saturate the network pipeline via asynchronous I/O during the initial data load.

---

## 🛠️ Setup & Usage

### 1. Prerequisites
* [cite_start]**Docker Desktop** (Ensuring the daemon is running) [cite: 27]
* [cite_start]**Locust** (For performance measurement) [cite: 18]

### 2. Start Infrastructure
```bash
docker-compose up -d
```

### 3. Seed Data (10M Keys)
This step prepares the "several GBs of data" required by the specification. For a POC of this scale, using a compiled language with asynchronous pipelining is the only way to saturate the 10Gbps link and finish the ingestion in under 2 minutes.

```bash
# Enter the seeder directory
cd rust-seeder

# Run the high-performance ingestion tool
# This uses 'cargo' to compile and run the optimized Rust binary
cargo run --release
```

### 4. Run Benchmark
Once the data is seeded, execute the Locust load test to verify that the application maintains **20,000 RPS** while the database sustains **300,000 OPS**.

```bash
# Run from the project root
# This launches the Locust web interface on http://localhost:8089
locust -f locustfile.py --host=http://localhost:8080
```

To reach these extreme limits during the test, ensure your local environment is tuned:

* **Increase File Descriptors:** `ulimit -n 65535` (Prevents "Too many open files" errors under 20k RPS).
* **Locust Workers:** Run Locust in distributed mode (`--master` and `--worker`) if a single CPU core cannot generate enough traffic to stress the PHP container.

---

## 📈 Performance Optimization Guide

As per the senior developer requirements, below is the strategy for maximizing Memcached/Dragonfly performance:

### How to achieve ~300,000 OPS:
* **Pipelining & Multi-Ops:** Use `MGET` and `MSET` to batch requests. Reducing the number of network round-trips is the single most effective way to scale OPS.
* **Persistent Connections:** Always utilize `pconnect()`. Opening a new TCP/Unix socket for every request will exhaust the ephemeral port range under high load and introduce massive latency.
* **Binary Serialization:** Prefer `igbinary` or `msgpack` over standard JSON. These formats significantly reduce payload size and the CPU cycles required for serialization/deserialization.
* **Worker Mode:** Keeping the application resident in memory via FrankenPHP is mandatory to avoid the "cold start" latency and file-system overhead of traditional PHP-FPM.


### Performance Anti-patterns (What NOT to do):
* **Avoid `KEYS *`:** Never use this in a production-scale environment. On a 10M+ key dataset, it will block the entire database engine, causing a total service timeout for all clients.
* **Synchronous File I/O:** Logging to disk or using standard file-based sessions will create a critical bottleneck at 20k RPS. All ephemeral data must reside in the high-performance memory store.
* **Large Value Blobs:** Storing massive objects (>1MB) in single keys leads to memory fragmentation and increases the risk of network head-of-line blocking.

---

## 🧪 Project Specifications
* **PHP Version:** 8.4+ (Strict Types Enabled)
* **Coding Standard:** PSR-12 compliant
* **Environment:** Dockerized / K8s Ready
* **Targets:** 20k RPS / 300k OPS / 10M+ Keys

---

**Contact for Review:**
Štěpán Fichtner ([fichtner@deepvision.cz](mailto:fichtner@deepvision.cz))