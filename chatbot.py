import requests
import json
from datetime import datetime

class GeminiChatbot:
    def __init__(self, api_key):
        self.api_key = api_key
        self.base_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent"
        self.conversation_history = []
        
    def get_response(self, prompt):
        try:
            url = f"{self.base_url}?key={self.api_key}"
            
            headers = {
                "Content-Type": "application/json"
            }
            
            data = {
                "contents": [{
                    "parts": [{
                        "text": prompt
                    }]
                }]
            }
            
            response = requests.post(url, headers=headers, json=data)
            response.raise_for_status()  # Raise an exception for bad status codes
            
            result = response.json()
            if 'candidates' in result:
                return result['candidates'][0]['content']['parts'][0]['text']
            return "Sorry, I couldn't generate a response."
            
        except requests.exceptions.RequestException as e:
            return f"Error: {str(e)}"

    def save_conversation(self, filename="chat_history.txt"):
        timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        with open(filename, "a", encoding="utf-8") as f:
            for message in self.conversation_history:
                f.write(f"[{timestamp}] {message}\n")
            f.write("-" * 50 + "\n")  # Add separator between conversations
        self.conversation_history = []  # Clear history after saving

# Example usage
if __name__ == "__main__":
    API_KEY = "YOUR_API_KEY"  # Replace with your actual API key
    chatbot = GeminiChatbot(API_KEY)
    
    print("Chat started. Type 'quit', 'exit', or 'bye' to end the conversation.")
    
    while True:
        user_input = input("You: ")
        if user_input.lower() in ['quit', 'exit', 'bye']:
            # Save final conversation before exiting
            chatbot.conversation_history.append(f"User: {user_input}")
            chatbot.save_conversation()
            print("Conversation saved. Goodbye!")
            break
            
        response = chatbot.get_response(user_input)
        # Store the conversation
        chatbot.conversation_history.append(f"User: {user_input}")
        chatbot.conversation_history.append(f"Bot: {response}")
        print(f"Bot: {response}") 