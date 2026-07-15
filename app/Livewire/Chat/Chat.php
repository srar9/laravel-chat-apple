<?php

namespace App\Livewire\Chat;

use Livewire\Component;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;

#[Title('Mensajes')]
class Chat extends Component
{
    public $selectedConversationId = null;
    public $searchQuery = '';
    public $messageContent = '';
    
    // Group creation state
    public $showGroupModal = false;
    public $groupName = '';
    public $selectedGroupUsers = [];

    protected $queryString = ['selectedConversationId' => ['except' => null, 'as' => 'chat']];

    public function mount()
    {
        if (!Auth::check()) {
            return redirect()->to('/login');
        }
    }

    public function getConversationsProperty()
    {
        if (!Auth::check()) return collect();

        return Auth::user()->conversations()
            ->with(['users', 'latestMessage'])
            ->get()
            ->sortByDesc(function ($conversation) {
                return $conversation->latestMessage ? $conversation->latestMessage->created_at : $conversation->created_at;
            });
    }

    public function getSearchResultsProperty()
    {
        if (empty($this->searchQuery)) {
            return collect();
        }

        return User::where('id', '!=', Auth::id())
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->searchQuery . '%')
                      ->orWhere('email', 'like', '%' . $this->searchQuery . '%');
            })
            ->limit(10)
            ->get();
    }

    public function getAllUsersProperty()
    {
        if (!Auth::check()) return collect();
        return User::where('id', '!=', Auth::id())->get();
    }

    public function getSelectedConversationProperty()
    {
        if (!$this->selectedConversationId) {
            return null;
        }

        return Conversation::with(['users', 'messages.user'])->find($this->selectedConversationId);
    }

    public function selectConversation($id)
    {
        $this->selectedConversationId = $id;
        $this->messageContent = '';
        $this->searchQuery = '';
        
        $this->markAsRead($id);
        
        // Dispatch event to scroll to bottom after DOM updates
        $this->dispatch('chat-changed');
    }

    public function startPrivateChat($userId)
    {
        $currentUserId = Auth::id();

        // Check if there is already a 1-to-1 conversation between the two users
        $existingConversation = Conversation::where('is_group', false)
            ->whereHas('users', function ($query) use ($currentUserId) {
                $query->where('users.id', $currentUserId);
            })
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->first();

        if ($existingConversation) {
            $this->selectConversation($existingConversation->id);
            return;
        }

        // Otherwise create one
        $conversation = Conversation::create([
            'is_group' => false,
            'created_by' => $currentUserId,
        ]);

        $conversation->users()->attach([$currentUserId, $userId]);

        $this->selectConversation($conversation->id);
    }

    public function sendMessage()
    {
        if (empty(trim($this->messageContent))) {
            return;
        }

        if (!$this->selectedConversationId) {
            return;
        }

        $message = Message::create([
            'conversation_id' => $this->selectedConversationId,
            'user_id' => Auth::id(),
            'content' => $this->messageContent,
        ]);

        $this->messageContent = '';
        $this->markAsRead($this->selectedConversationId);
        
        $this->dispatch('message-sent');
    }

    public function openGroupModal()
    {
        $this->groupName = '';
        $this->selectedGroupUsers = [];
        $this->showGroupModal = true;
    }

    public function closeGroupModal()
    {
        $this->showGroupModal = false;
    }

    public function createGroup()
    {
        $this->validate([
            'groupName' => 'required|string|max:100',
            'selectedGroupUsers' => 'required|array|min:1',
        ], [
            'groupName.required' => 'El nombre del grupo es obligatorio.',
            'selectedGroupUsers.required' => 'Debes seleccionar al menos a un participante.',
            'selectedGroupUsers.min' => 'Debes seleccionar al menos a un participante.',
        ]);

        $currentUserId = Auth::id();

        // Create the group conversation
        $conversation = Conversation::create([
            'name' => $this->groupName,
            'is_group' => true,
            'created_by' => $currentUserId,
        ]);

        // Attach all users including current user
        $userIds = array_merge([$currentUserId], array_map('intval', $this->selectedGroupUsers));
        $conversation->users()->attach($userIds);

        $this->closeGroupModal();
        $this->selectConversation($conversation->id);
    }

    public function markAsRead($conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if ($conversation) {
            $conversation->users()->updateExistingPivot(Auth::id(), [
                'last_read_at' => now(),
            ]);
        }
    }

    public function getAvatarColor($name)
    {
        $sum = 0;
        for ($i = 0; $i < strlen($name); $i++) {
            $sum += ord($name[$i]);
        }
        $index = ($sum % 7) + 1; // Colors are avatar-bg-1 to avatar-bg-7
        return 'avatar-bg-' . $index;
    }

    public function getInitials($name)
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
            if (strlen($initials) >= 2) break;
        }
        return $initials ?: 'U';
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->to('/login');
    }

    public function render()
    {
        return view('livewire.chat.main');
    }
}
