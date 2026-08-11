/**
 * TelegrOllama - Frontend Application Logic
 */

const App = {
    state: {
        characters: [],
        activeCharacter: null,
        isTyping: false,
        searchQuery: ''
    },

    // DOM Elements
    elements: {
        sidebar: document.getElementById('sidebar'),
        characterList: document.getElementById('character-list'),
        emptyState: document.getElementById('empty-state'),
        conversationContainer: document.getElementById('conversation-container'),
        messagesContainer: document.getElementById('messages-container'),
        chatHeader: document.getElementById('chat-header'),
        chatForm: document.getElementById('chat-form'),
        messageInput: document.getElementById('message-input'),
        btnSend: document.getElementById('btn-send'),
        searchCharacters: document.getElementById('search-characters'),
        modalOverlay: document.getElementById('modal-overlay'),
        modalContent: document.getElementById('modal-content'),
        btnNewChar: document.getElementById('btn-new-character'),
        btnCreateCharEmpty: document.getElementById('btn-create-char-empty')
    },

    async init() {
        this.bindEvents();
        await this.loadCharacters();
    },

    bindEvents() {
        // Search
        this.elements.searchCharacters.addEventListener('input', (e) => {
            this.state.searchQuery = e.target.value;
            this.renderCharacterList();
        });

        // New Character Button
        this.elements.btnNewChar.addEventListener('click', () => this.showCreateModal());
        this.elements.btnCreateCharEmpty.addEventListener('click', () => this.showCreateModal());

        // Chat Form
        this.elements.chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSendMessage();
        });

        // Textarea auto-resize and Enter key
        this.elements.messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.handleSendMessage();
            }
        });

        // Modal close on overlay click
        this.elements.modalOverlay.addEventListener('click', (e) => {
            if (e.target === this.elements.modalOverlay) this.closeModal();
        });
    },

    async loadCharacters() {
        try {
            const response = await fetch('/api.php?action=list_characters');
            const data = await response.json();
            if (data.error) throw new Error(data.error);
            this.state.characters = data;
            this.renderCharacterList();
        } catch (err) {
            console.error('Failed to load characters:', err);
            alert('Error loading characters: ' + err.message);
        }
    },

    renderCharacterList() {
        const filtered = this.state.characters.filter(c => 
            c.name.toLowerCase().includes(this.state.searchQuery.toLowerCase())
        );

        this.elements.characterList.innerHTML = filtered.map(char => `
            <div class="character-item ${this.state.activeCharacter?.id === char.id ? 'active' : ''}" 
                 onclick="App.selectCharacter(${char.id})">
                <div class="avatar">${this.getInitials(char.name)}</div>
                <div class="char-info">
                    <div class="char-name">${this.escapeHtml(char.name)}</div>
                    <div class="char-preview">${this.escapeHtml(char.last_message || 'No messages yet')}</div>
                </div>
            </div>
        `).join('');
    },

    getInitials(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    },

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    async selectCharacter(id) {
        try {
            const response = await fetch(`/api.php?action=get_character&id=${id}`);
            const character = await response.json();
            if (character.error) throw new Error(character.error);

            this.state.activeCharacter = character;
            this.renderCharacterList(); // Update active state in sidebar
            this.showConversation(character);
        } catch (err) {
            console.error('Failed to select character:', err);
        }
    },

    async showConversation(character) {
        this.elements.emptyState.classList.add('hidden');
        this.elements.conversationContainer.classList.remove('hidden');

        // Render Header
        this.elements.chatHeader.innerHTML = `
            <div class="header-info">
                <div class="header-avatar">${this.getInitials(character.name)}</div>
                <div class="header-details">
                    <h3>${this.escapeHtml(character.name)}</h3>
                    <p>${this.escapeHtml(character.description || 'No description')}</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn-icon" onclick="App.showEditModal(${character.id})" title="Edit Character">
                    <svg viewBox="0 0 640 640" width="20" height="20"><path fill="currentColor" d="M320.85,151.725l139.6,139.6l-238,237.9l-139.6-139.6L320.85,151.725z M607.65,124.125l-119.7-119.7 c-5.9-5.9-15.5-5.9-21.4,0l-104,104l141,141l104-104C613.55,139.525,613.55,130.025,607.65,124.125z M0.55,592.825 c-1.4,5.3,0.1,10.9,3.9,14.8c3.9,3.9,9.5,5.4,14.8,3.9l165.5-44.6l-139.6-139.6L0.55,592.825z" /></svg>
                </button>
                <button class="btn-icon" onclick="App.deleteCharacter(${character.id})" title="Delete Character">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19V4M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z" /></svg>
                </button>
            </div>
        `;

        // Load Messages
        await this.loadMessages(character.id);
    },

    async loadMessages(charId) {
        try {
            const response = await fetch(`/api.php?action=get_messages&character_id=${charId}`);
            const messages = await response.json();
            if (messages.error) throw new Error(messages.error);

            this.renderMessages(messages);
        } catch (err) {
            console.error('Failed to load messages:', err);
        }
    },

    renderMessages(messages) {
        this.elements.messagesContainer.innerHTML = messages.map(msg => `
            <div class="message ${msg.role}">
                ${this.escapeHtml(msg.content)}
                <span class="message-time">${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
            </div>
        `).join('');
        this.scrollToBottom();
    },

    async handleSendMessage() {
        const content = this.elements.messageInput.value.trim();
        if (!content || !this.state.activeCharacter || this.state.isTyping) return;

        const charId = this.state.activeCharacter.id;
        
        // 1. Optimistically add user message to UI
        this.appendMessage({ role: 'user', content, created_at: new Date().toISOString() });
        this.elements.messageInput.value = '';
        this.elements.messageInput.style.height = 'auto';

        // 2. Show thinking indicator
        this.setTyping(true);

        try {
            const response = await fetch(`/api.php?action=send_message&character_id=${charId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content })
            });

            const data = await response.json();
            if (data.error) throw new Error(data.error);

            // 3. Add AI message to UI
            this.appendMessage({ role: 'assistant', content: data.content, created_at: new Date().toISOString() });
            
            // Update character list preview in sidebar
            this.state.characters = this.state.characters.map(c => 
                c.id === charId ? { ...c, last_message: data.content.substring(0, 50), updated_at: new Date().toISOString() } : c
            );
            this.renderCharacterList();

        } catch (err) {
            console.error('Send error:', err);
            alert('Error sending message: ' + err.message);
        } finally {
            this.setTyping(false);
        }
    },

    appendMessage(msg) {
        const msgEl = document.createElement('div');
        msgEl.className = `message ${msg.role}`;
        msgEl.innerHTML = `
            ${this.escapeHtml(msg.content)}
            <span class="message-time">${new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
        `;
        this.elements.messagesContainer.appendChild(msgEl);
        this.scrollToBottom();
    },

    setTyping(isTyping) {
        this.state.isTyping = isTyping;
        this.elements.btnSend.disabled = isTyping;
        
        if (isTyping) {
            const indicator = document.createElement('div');
            indicator.id = 'thinking-indicator';
            indicator.className = 'message assistant thinking-indicator';
            indicator.innerHTML = `<div class="dot"></div><div class="dot"></div><div class="dot"></div>`;
            this.elements.messagesContainer.appendChild(indicator);
        } else {
            const indicator = document.getElementById('thinking-indicator');
            if (indicator) indicator.remove();
        }
        this.scrollToBottom();
    },

    scrollToBottom() {
        this.elements.messagesContainer.scrollTop = this.elements.messagesContainer.scrollHeight;
    },

    // Modals
    showCreateModal() {
        this.openModal(`
            <h2>Create New Character</h2>
            <form id="create-char-form">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required placeholder="e.g. Luna">
                </div>
                <div class="form-group">
                    <label>Description (System Prompt)</label>
                    <textarea name="description" rows="4" placeholder="Describe their personality..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="App.closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Create Character</button>
                </div>
            </form>
        `);

        document.getElementById('create-char-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const payload = {
                name: formData.get('name'),
                description: formData.get('description')
            };

            try {
                const res = await fetch('/api.php?action=create_character', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.error) throw new Error(data.error);
                this.closeModal();
                await this.loadCharacters();
                await this.selectCharacter(data.id);
            } catch (err) {
                alert(err.message);
            }
        });
    },

    async showEditModal(id) {
        const char = await (await fetch(`/api.php?action=get_character&id=${id}`)).json();
        this.openModal(`
            <h2>Edit Character</h2>
            <form id="edit-char-form">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="${this.escapeHtml(char.name)}" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4">${this.escapeHtml(char.description || '')}</textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="App.closeModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        `);

        document.getElementById('edit-char-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const payload = {
                name: formData.get('name'),
                description: formData.get('description')
            };

            try {
                const res = await fetch(`/api.php?action=update_character&id=${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.error) throw new Error(data.error);
                this.closeModal();
                await this.loadCharacters();
                await this.selectCharacter(id);
            } catch (err) {
                alert(err.message);
            }
        });
    },

    async deleteCharacter(id) {
        if (!confirm('Are you sure you want to delete this character? This will also delete all conversation history.')) return;

        try {
            const res = await fetch(`/api.php?action=delete_character&id=${id}`, { method: 'POST' });
            const data = await res.json();
            if (data.error) throw new Error(data.error);
            
            this.state.activeCharacter = null;
            this.elements.conversationContainer.classList.add('hidden');
            this.elements.emptyState.classList.remove('hidden');
            await this.loadCharacters();
        } catch (err) {
            alert(err.message);
        }
    },

    openModal(html) {
        this.elements.modalContent.innerHTML = html;
        this.elements.modalOverlay.classList.remove('hidden');
    },

    closeModal() {
        this.elements.modalOverlay.classList.add('hidden');
    }
};

// Initialize the app
document.addEventListener('DOMContentLoaded', () => App.init());
