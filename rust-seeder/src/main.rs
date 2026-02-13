use redis::Commands;
use std::time::Instant;

fn main() -> redis::RedisResult<()> {
    let redis_url = std::env::var("REDIS_URL").unwrap_or_else(|_| "redis://127.0.0.1:6379/".to_string());
    let client = redis::Client::open(redis_url)?;
    let mut con = client.get_connection()?;
    let count = 10_000_000;
    let batch_size = 10_000; // Optimal batch size to avoid memory overflow

    println!("🚀 Starting high-speed seed of {} keys...", count);
    let start = Instant::now();

    for i in 0..(count / batch_size) {
        let mut pipe = redis::pipe();
        for j in 0..batch_size {
            let key_idx = i * batch_size + j;
            pipe.set(format!("user:session:{}", key_idx), "{\"status\":\"active\",\"role\":\"senior\"}");
        }
        // Execute the batch in a single network call
        let _: () = pipe.query(&mut con)?;
        
        if i % 100 == 0 {
            println!("✅ Progress: {}%", (i as f32 / (count / batch_size) as f32 * 100.0) as i32);
        }
    }

    println!("✨ Finished in {:?}. Speed: {} keys/sec", start.elapsed(), count as f64 / start.elapsed().as_secs_f64());
    Ok(())
}