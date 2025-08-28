# Migration Plan: PHP to Python-Only Architecture

## Overview
Migrate from hybrid PHP/Python system to pure Python web application with Flask/Django backend and modern frontend.

## Current Architecture Issues
1. **Complexity**: Dual-language maintenance burden
2. **Performance**: JSON serialization overhead between PHP/Python
3. **Deployment**: Multiple runtime dependencies (PHP + Python)
4. **Development**: Context switching between languages
5. **Debugging**: Harder to trace issues across language boundaries

## Target Architecture

### Backend: Python Flask/FastAPI
- **Flask Web Framework**: RESTful API server
- **SQLAlchemy ORM**: Database abstraction 
- **OR-Tools Integration**: Direct Python optimization
- **Celery**: Background task processing for long optimizations
- **Redis/RabbitMQ**: Message queue for async processing

### Frontend: Modern JavaScript SPA
- **React/Vue.js**: Component-based UI
- **TypeScript**: Type-safe frontend development
- **Material-UI/Tailwind**: Modern component library
- **WebSocket**: Real-time optimization progress updates

### Database: PostgreSQL/MySQL
- **Schema Migration**: Preserve existing data structure
- **Alembic**: Database migrations management
- **Connection Pooling**: Better performance

## Migration Phases

### Phase 1: Core Python Web App (Week 1-2)
1. **Flask Application Setup**
   ```bash
   pip install flask flask-sqlalchemy flask-migrate celery redis
   ```

2. **Database Models Migration**
   - Convert PHP models to SQLAlchemy
   - Preserve existing MySQL schema
   - Add migration scripts

3. **API Endpoints**
   - `/api/teams` - Team management
   - `/api/matches` - Match scheduling
   - `/api/constraints` - Constraint configuration
   - `/api/planning` - Optimization execution
   - `/api/assignments` - Assignment management

4. **Basic Web Interface**
   - HTML templates with Jinja2
   - Bootstrap CSS (existing styling)
   - Basic CRUD operations

### Phase 2: Optimization Integration (Week 2-3)
1. **Direct OR-Tools Integration**
   - Remove JSON serialization overhead
   - In-memory constraint processing
   - Real-time progress updates

2. **Background Task Processing**
   - Celery workers for optimization
   - Redis for task queuing
   - WebSocket progress updates

3. **Advanced Constraint Engine**
   - Template-based constraint system
   - Custom constraint validation
   - Constraint conflict detection

### Phase 3: Enhanced Frontend (Week 3-4)
1. **React/Vue.js SPA**
   - Component-based architecture
   - Real-time updates
   - Interactive constraint editor

2. **Advanced UI Features**
   - Drag-drop assignment interface
   - Visual constraint violation indicators
   - Interactive Gantt charts
   - Export/import functionality

### Phase 4: Production Deployment (Week 4-5)
1. **Containerization**
   - Docker containers
   - Docker Compose development
   - Kubernetes production deployment

2. **Performance Optimization**
   - Database indexing
   - Query optimization
   - Caching strategies

3. **Monitoring & Logging**
   - Application monitoring
   - Performance metrics
   - Error tracking

## Benefits of Python-Only Architecture

### Development Benefits
- **Single Language**: Python throughout stack
- **Faster Development**: No context switching
- **Better Testing**: Unified test framework
- **Easier Debugging**: Single runtime environment

### Performance Benefits  
- **No Serialization**: Direct memory operations
- **Better Optimization**: Native OR-Tools integration
- **Faster API**: No PHP→Python→PHP roundtrips
- **Real-time Updates**: WebSocket communication

### Maintenance Benefits
- **Simpler Deployment**: Single runtime
- **Better Documentation**: Python docstrings/type hints
- **Version Management**: Single dependency tree
- **Code Reuse**: Shared utilities/models

## Implementation Strategy

### Week 1: Foundation
- [ ] Set up Flask application structure
- [ ] Convert database models to SQLAlchemy
- [ ] Create basic API endpoints
- [ ] Set up development environment

### Week 2: Core Features
- [ ] Implement team/match/constraint CRUD
- [ ] Direct OR-Tools integration
- [ ] Basic optimization workflow
- [ ] Simple web interface

### Week 3: Advanced Features
- [ ] Background task processing
- [ ] Real-time progress updates
- [ ] Advanced constraint validation
- [ ] Enhanced UI components

### Week 4: Polish & Deploy
- [ ] Performance optimization
- [ ] Error handling & logging
- [ ] Production deployment setup
- [ ] Documentation & testing

## Migration Commands

### Start Migration
```bash
# Create new Python app structure
mkdir jury_planner_python
cd jury_planner_python

# Set up virtual environment
python -m venv venv
source venv/bin/activate

# Install dependencies
pip install flask flask-sqlalchemy flask-migrate celery redis ortools

# Initialize Flask app
flask db init
flask db migrate -m "Initial migration"
flask db upgrade
```

### Data Migration
```bash
# Export existing data
python migrate_data.py export --source mysql://user:pass@host/jury_planner

# Import to new system  
python migrate_data.py import --target postgresql://user:pass@host/jury_planner
```

## Risk Mitigation

### Parallel Development
- Keep PHP system running during migration
- Gradual feature migration with A/B testing
- Rollback plan if issues arise

### Data Integrity
- Database backup before migration
- Data validation scripts
- Parallel data verification

### User Training
- Document new interface changes
- Provide migration guide
- Support during transition period

## Success Metrics

### Performance Improvements
- 50%+ faster optimization execution
- 70% reduction in memory usage
- Real-time progress updates

### Development Efficiency  
- 40% faster feature development
- Unified codebase maintenance
- Better error handling/debugging

### User Experience
- Modern, responsive interface
- Real-time feedback
- Better mobile support
