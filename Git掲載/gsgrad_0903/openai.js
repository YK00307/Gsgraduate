const API_KEY = "";

document.getElementById("callBtn").addEventListener("click", async () => {
  const response = await fetch("https://api.openai.com/v1/chat/completions", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": `Bearer ${API_KEY}`
    },
    body: JSON.stringify({
      model: "gpt-4o-mini",   // 軽いモデルでOK
      messages: [
        { role: "system", content: "You are a helpful assistant." },
        { role: "user", content: "こんにちは！英語に翻訳して" }
      ]
    })
  });

  const data = await response.json();
  console.log(data);
  document.getElementById("result").textContent = 
    data.choices[0].message.content;
});
