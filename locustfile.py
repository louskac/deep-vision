# This file simulates heavy traffic by randomly hitting the 10 million keys that were seeded
from locust import HttpUser, task, between
import random

class HighPerfUser(HttpUser):
    # No wait time to simulate maximum stress
    wait_time = between(0, 0)

    @task
    def get_session(self):
        # Target the 10M keys we seeded with Rust
        user_id = random.randint(0, 9999999)
        self.client.get(f"/user/session/{user_id}", name="/user/session/[id]")

    @task(3) # Weights this task higher
    def check_stats(self):
        self.client.get("/stats")