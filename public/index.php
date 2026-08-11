<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TelegrOllama</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div id="app">
        <!-- Sidebar -->
        <aside id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon"></div>
                    <h1>TelegrOllama</h1>
                </div>
            </div>

            <div class="sidebar-actions">
                <button id="btn-new-character" class="btn-primary">+ New Character</button>
                <div class="search-container">
                    <input type="text" id="search-characters" placeholder="Search characters...">
                </div>
            </div>

            <div id="character-list" class="character-list">
                <!-- Characters will be injected here -->
            </div>
        </aside>

        <!-- Main Chat Area -->
        <main id="chat-area">
            <!-- Empty State / Conversation Header / Messages / Composer -->
            <div id="empty-state" class="empty-state hidden">
                <div class="empty-state-content">
                    <div class="empty-icon">✨</div>
                    <h2>Start a new conversation</h2>
                    <p>Create a character and start chatting with your local AI.</p>
                    <button id="btn-create-char-empty" class="btn-primary">+ Create Character</button>
                </div>
            </div>

            <div id="conversation-container" class="conversation-container hidden">
                <header id="chat-header">
                    <!-- Header content injected here -->
                </header>

                <div id="messages-container" class="messages-container">
                    <!-- Messages will be injected here -->
                </div>

                <div id="composer-container" class="composer-container">
                    <form id="chat-form">
                        <textarea id="message-input" placeholder="Type a message..." rows="1"></textarea>
                        <button type="submit" id="btn-send" class="btn-icon">
                            <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M2,21L23,12L2,3V10L17,12L2,14V21Z" /></svg>
                        </button>
                    </form>
                </div>
            </div>
        </main>

        <!-- Modals -->
        <div id="modal-overlay" class="modal-overlay hidden">
            <div class="modal">
                <div id="modal-content">
                    <!-- Modal content injected here -->
                </div>
            </div>
        </div>
    </div>

    <script src="/assets/js/app.js"></script>
</body>
</html>
