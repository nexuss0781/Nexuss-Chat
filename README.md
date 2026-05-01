# NexusChat - Professional Chat Application

## Project Overview
NexusChat is a full-featured real-time chat application with complete authentication system, designed for deployment on InfinityFree hosting.

## Waterfall SDLC Model Implementation

### Phase 1: Requirements Analysis
- User authentication (register, login, logout, password reset)
- Real-time messaging system
- User presence status
- Message history
- Contact management
- Group chats
- File sharing
- Message encryption
- Responsive UI design

### Phase 2: System Design
- Database schema design
- API architecture
- Frontend component structure
- Security implementation
- Deployment strategy

### Phase 3: Implementation
- Backend PHP development
- Frontend vanilla JavaScript
- Database integration
- Authentication system
- Chat functionality

### Phase 4: Testing
- Unit testing
- Integration testing
- Security testing
- Performance testing

### Phase 5: Deployment
- InfinityFree configuration
- Database setup
- Production deployment

### Phase 6: Maintenance
- Bug fixes
- Feature updates
- Performance optimization
- Security patches

## Technical Stack

### Frontend
- HTML5/CSS3
- TailwindCSS (CDN)
- Vanilla JavaScript
- Lucide Icons (CDN)
- Alpine.js for reactivity

### Backend
- Pure PHP 8.x
- MySQL/MariaDB
- PDO for database access
- Session-based authentication
- RESTful API design

### Security Features
- Password hashing (bcrypt)
- SQL injection prevention
- XSS protection
- CSRF tokens
- Input validation
- Rate limiting

## Directory Structure
```
/workspace
├── docs/                    # Documentation
│   ├── analysis/           # Requirements analysis
│   ├── design/             # System design documents
│   ├── implementation/     # Implementation guides
│   ├── testing/            # Test plans and results
│   ├── deployment/         # Deployment instructions
│   └── maintenance/        # Maintenance procedures
├── public/                 # Web root
│   ├── index.php          # Main entry point
│   ├── login.php          # Login page
│   ├── register.php       # Registration page
│   ├── chat.php           # Chat interface
│   ├── assets/            # Static resources
│   │   ├── css/           # Stylesheets
│   │   └── js/            # JavaScript files
│   └── api/               # API endpoints
├── includes/              # PHP includes
│   ├── auth.php           # Authentication functions
│   ├── db.php             # Database connection
│   ├── chat.php           # Chat functions
│   └── utils.php          # Utility functions
└── config/                # Configuration files
    └── database.php       # Database configuration
```

## Features List

### Authentication System
- User registration with email verification
- Secure login with session management
- Password reset functionality
- Remember me option
- Account deactivation
- Profile management

### Chat System
- Real-time messaging
- One-on-one conversations
- Group chats
- Message read receipts
- Online/offline status
- Typing indicators
- Message search
- File attachments
- Emoji support
- Message reactions

### Advanced Features
- Dark/Light theme toggle
- Responsive design
- Push notifications
- Message encryption
- Chat export
- Blocked users management
- Report inappropriate content
- Admin dashboard

## Database Schema
- users table
- conversations table
- messages table
- conversation_participants table
- user_status table
- attachments table
- notifications table

## API Endpoints
- POST /api/auth/register
- POST /api/auth/login
- POST /api/auth/logout
- GET /api/users
- GET /api/conversations
- POST /api/messages
- GET /api/messages/{conversation_id}
- PUT /api/users/status

## Deployment Target
- Hosting: InfinityFree
- Domain: Nexuss-Chat.gt.tc
- Database: MySQL via InfinityFree
- PHP Version: 8.x

## License
MIT License - See LICENSE file for details
