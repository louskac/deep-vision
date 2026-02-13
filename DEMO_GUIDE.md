# 🎯 Interview Demonstration Guide

This guide outlines how to showcase the high-concurrency capabilities of the PHP Worker architecture.

## 1. Preparation
Ensure the environment is up and data is seeded.
```bash
./warmup.sh
```

## 2. Show the Dashboard
Open your browser to: **[http://localhost](http://localhost)**
*   **Highlight:** The sleek UI shows real-time metrics pulled directly from DragonflyDB.
*   **Highlight:** Mention **FrankenPHP Worker Mode** keeping the app resident in memory.

## 3. Launch the Stress Test
Execute Locust to simulate 20,000 requests per second.
```bash
locust -f locustfile.py --host=http://localhost:8080
```
1.  Open `http://localhost:8089/`.
2.  Set **Users: 500** and **Spawn Rate: 50**.
3.  Watch the **RPS** climb towards **20k**.
4.  Switch back to the PHP Dashboard to see the **OPS** hit **300k+**.

## 4. Key Talking Points
*   **DragonflyDB:** Explain why we chose it over Redis (multi-threaded, shared-nothing architecture).
*   **Binary Protocols:** Mention that internal data is serialized with `igbinary` for speed.
*   **Rust Seeder:** Explain that a compiled language with async I/O was necessary to saturate the pipeline for 10M keys.
*   **Pipelining:** Discuss how we batch requests to minimize network round-trips.

---
*Created by Antigravity for DEEP VISION Proof of Concept.*
