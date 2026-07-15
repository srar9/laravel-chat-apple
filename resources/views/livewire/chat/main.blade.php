<div class="chat-layout" x-data="{
    scrollToBottom() {
        this.$nextTick(() => {
            const feed = this.$refs.feed;
            if (feed) {
                feed.scrollTop = feed.scrollHeight;
            }
        });
    }
}" x-init="scrollToBottom()" @chat-changed.window="scrollToBottom()" @message-sent.window="scrollToBottom()">

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="sidebar-header-top">
                <div class="header-user-info" style="gap: 8px;">
                    <div class="chat-avatar {{ $this->getAvatarColor(Auth::user()->name) }}" style="width: 32px; height: 32px; font-size: 12px; margin-right: 0;">
                        {{ $this->getInitials(Auth::user()->name) }}
                    </div>
                    <span style="font-weight: 600; font-size: 15px;">{{ Auth::user()->name }}</span>
                </div>
                <div class="sidebar-actions">
                    <!-- Create Group Action -->
                    <button wire:click="openGroupModal" class="action-btn" title="Nuevo Grupo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    </button>
                    <!-- Logout Action -->
                    <button wire:click="logout" class="action-btn" title="Cerrar Sesión" style="color: var(--danger-red);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </button>
                </div>
            </div>
            
            <!-- Search bar -->
            <div class="search-container">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" wire:model.live.debounce.150ms="searchQuery" class="search-input" placeholder="Buscar personas...">
            </div>
        </div>

        <!-- Chat List / Search Results -->
        <div class="chat-list" wire:poll.keep-alive.3s>
            @if (!empty($searchQuery))
                <!-- Search Results -->
                <div style="padding: 10px 12px 6px 12px; font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">
                    Contactos encontrados
                </div>
                @forelse ($this->searchResults as $user)
                    <div wire:click="startPrivateChat({{ $user->id }})" class="chat-item">
                        <div class="chat-avatar {{ $this->getAvatarColor($user->name) }}">
                            {{ $this->getInitials($user->name) }}
                        </div>
                        <div class="chat-details">
                            <div class="chat-name">{{ $user->name }}</div>
                            <div class="chat-preview">{{ $user->email }}</div>
                        </div>
                    </div>
                @empty
                    <div style="padding: 20px; text-align: center; color: var(--text-secondary); font-size: 14px;">
                        No se encontraron usuarios
                    </div>
                @endforelse
            @else
                <!-- Active Chats -->
                @forelse ($this->conversations as $conversation)
                    @php
                        $displayName = $conversation->is_group 
                            ? $conversation->name 
                            : ($conversation->users->firstWhere('id', '!=', Auth::id())->name ?? 'Usuario de Chat');
                        
                        $isUnread = false;
                        if ($conversation->latestMessage) {
                            $lastReadAt = $conversation->pivot->last_read_at;
                            $isUnread = !$lastReadAt || $conversation->latestMessage->created_at->gt($lastReadAt);
                        }
                    @endphp
                    <div wire:click="selectConversation({{ $conversation->id }})" 
                         class="chat-item {{ $selectedConversationId == $conversation->id ? 'active' : '' }}">
                        
                        <div class="chat-avatar {{ $this->getAvatarColor($displayName) }}">
                            {{ $this->getInitials($displayName) }}
                            @if ($isUnread)
                                <div class="unread-indicator"></div>
                            @endif
                        </div>
                        <div class="chat-details">
                            <div class="chat-details-top">
                                <div class="chat-name" style="{{ $isUnread ? 'font-weight: 700;' : '' }}">
                                    {{ $displayName }}
                                </div>
                                <div class="chat-time">
                                    {{ $conversation->latestMessage ? $conversation->latestMessage->created_at->format('H:i') : '' }}
                                </div>
                            </div>
                            <div class="chat-preview" style="{{ $isUnread ? 'font-weight: 600; color: var(--text-primary);' : '' }}">
                                @if ($conversation->latestMessage)
                                    {{ $conversation->latestMessage->user_id == Auth::id() ? 'Tú: ' : ($conversation->is_group ? $conversation->latestMessage->user->name . ': ' : '') }}{{ Str::limit($conversation->latestMessage->content, 35) }}
                                @else
                                    Chat vacío
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="padding: 40px 20px; text-align: center; color: var(--text-secondary); font-size: 14px; line-height: 1.5;">
                        <span style="font-size: 24px; display: block; margin-bottom: 8px;">💬</span>
                        No hay conversaciones activas.<br>Busca personas arriba para empezar.
                    </div>
                @endforelse
            @endif
        </div>
    </div>

    <!-- Active Chat Window -->
    <div class="chat-window">
        @if ($selectedConversationId && $this->selectedConversation)
            @php
                $conversation = $this->selectedConversation;
                $chatTitle = $conversation->is_group 
                    ? $conversation->name 
                    : ($conversation->users->firstWhere('id', '!=', Auth::id())->name ?? 'Usuario de Chat');
                
                $chatSubtitle = $conversation->is_group 
                    ? $conversation->users->count() . ' miembros' 
                    : 'Chat privado';
            @endphp
            <!-- Header -->
            <div class="chat-window-header">
                <div class="header-user-info">
                    <div class="header-avatar {{ $this->getAvatarColor($chatTitle) }}">
                        {{ $this->getInitials($chatTitle) }}
                    </div>
                    <div>
                        <div class="header-name">{{ $chatTitle }}</div>
                        <div class="header-status">{{ $chatSubtitle }}</div>
                    </div>
                </div>
            </div>

            <!-- Messages List -->
            <div x-ref="feed" class="message-feed" wire:poll.keep-alive.3s>
                @forelse ($conversation->messages as $message)
                    @php
                        $isSent = $message->user_id == Auth::id();
                    @endphp
                    <div class="message-row {{ $isSent ? 'sent' : 'received' }}">
                        <div style="max-width: 65%;">
                            @if ($conversation->is_group && !$isSent)
                                <div class="message-sender-name">{{ $message->user->name }}</div>
                            @endif
                            <div class="message-bubble">
                                <div>{{ $message->content }}</div>
                                <div class="message-time">{{ $message->created_at->format('H:i') }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: var(--text-secondary); font-size: 14px;">
                        Envia un mensaje para iniciar la conversación
                    </div>
                @endforelse
            </div>

            <!-- Input Bar -->
            <form wire:submit.prevent="sendMessage" class="chat-input-bar">
                <input type="text" wire:model="messageContent" class="chat-text-input" placeholder="Escribe un mensaje..." required autocomplete="off">
                <button type="submit" class="send-btn" {{ empty(trim($messageContent)) ? 'disabled' : '' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </form>
        @else
            <!-- Empty State -->
            <div class="chat-window-empty">
                <div class="empty-icon"></div>
                <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 4px;">Mensajes de Antigravity</h3>
                <p style="font-size: 14px;">Selecciona una conversación o busca un contacto para comenzar.</p>
            </div>
        @endif
    </div>

    <!-- Create Group Modal -->
    @if ($showGroupModal)
        <div class="modal-overlay" wire:click.self="closeGroupModal">
            <div class="modal">
                <div class="modal-header">
                    <span class="modal-title">Nuevo Grupo</span>
                    <span class="modal-close" wire:click="closeGroupModal">&times;</span>
                </div>
                <form wire:submit.prevent="createGroup">
                    <div class="modal-body">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="groupName" class="form-label">Nombre del grupo</label>
                            <input type="text" id="groupName" wire:model="groupName" class="form-control" placeholder="Ej. Equipo de Diseño" required autofocus>
                            @error('groupName')
                                <span style="color: var(--danger-red); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Seleccionar participantes</label>
                            <div class="user-selection-list">
                                @forelse ($this->allUsers as $user)
                                    <label class="user-select-item" for="user-{{ $user->id }}">
                                        <div class="user-select-info">
                                            <div class="user-select-avatar {{ $this->getAvatarColor($user->name) }}">
                                                {{ $this->getInitials($user->name) }}
                                            </div>
                                            <div class="user-select-name">{{ $user->name }}</div>
                                        </div>
                                        <input type="checkbox" id="user-{{ $user->id }}" value="{{ $user->id }}" wire:model="selectedGroupUsers" class="user-select-checkbox">
                                    </label>
                                @empty
                                    <div style="text-align: center; color: var(--text-secondary); padding: 10px; font-size: 14px;">
                                        No hay otros usuarios registrados todavía.
                                    </div>
                                @endforelse
                            </div>
                            @error('selectedGroupUsers')
                                <span style="color: var(--danger-red); font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeGroupModal">Cancelar</button>
                        <button type="submit" class="btn" style="width: auto; padding-left: 20px; padding-right: 20px;">Crear Grupo</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
