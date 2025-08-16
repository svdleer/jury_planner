# 🔍 Database Analysis & Adaptation Plan

## 📊 Your Current Database Structure

Based on the analysis of your production database `mnc_jury`, here's what I found:

### 🏗️ Key Tables & Data:

1. **`all_matches`** (496 rows) - Complete match database
   - Contains all water polo matches from previous season
   - Fields: date_time, competition, class, home_team, away_team, location, match_id, sportlink_id

2. **`home_matches`** (253 rows) - MNC home games subset
   - Matches where MNC Dordrecht teams play at home
   - Same structure as all_matches but filtered for home games

3. **`jury_teams`** (10 rows) - Available jury teams
   - Teams that can provide jury services
   - Examples: "H1/H2", "MNC Dordrecht D", etc.

4. **`mnc_teams`** (26 rows) - MNC team registry  
   - Links team names to Sportlink IDs
   - Examples: "MNC Dordrecht G", "MNC Dordrecht J", "MNC Dordrecht H"

5. **`excluded_teams`** (5 rows) - Teams exempt from jury duty
   - Teams like "go", "h1", "h2" that don't provide jury

6. **`static_assignments`** (2 rows) - Fixed jury assignments
   - Predefined jury assignments for specific teams

7. **`jury_assignments`** (0 rows) - Current season assignments (empty)
8. **`jury_shifts`** (0 rows) - Scheduled jury shifts (empty) 
9. **`users`** (3 rows) - System users with roles

## 🎯 Adaptation Strategy

### Phase 1: Update Models to Match Your Schema ✅

I'll modify our Python models and PHP interface to work with your existing table structure instead of creating new ones.

### Phase 2: Data Integration ✅  

- Use `home_matches` as the primary source for jury planning
- Import your `jury_teams` and `excluded_teams` for constraint logic
- Respect `static_assignments` as fixed rules

### Phase 3: Smart Planning Logic ✅

- Build constraints based on your existing business rules
- Use team exclusions and static assignments 
- Generate optimized jury schedules for current season

## 🔧 Required Changes

### 1. Database Models
- Update `backend/models.py` to match your table structure
- Modify field names and relationships
- Add support for Sportlink integration

### 2. PHP Interface
- Update `php_interface/` to work with your table names
- Modify forms and displays to match your data structure
- Add support for competitions, classes, and locations

### 3. Planning Engine
- Adapt constraint logic for your jury assignment rules
- Support for points-based team selection
- Integration with static assignments and exclusions

## 📈 Data Insights

From your database, I can see:
- **496 total matches** across multiple competitions (GO14, GO12, JO16, Dames, MO18, etc.)
- **253 home matches** requiring jury assignments
- **Competition classes**: 2e klasse, 1e divisie, Eredivisie
- **Locations**: Sportboulevard, De Fakkel, De Kulk
- **Active jury system** with points tracking and team exclusions

## 🚀 Next Steps

1. **Update Models** - Modify our code to use your existing schema
2. **Test Connection** - Verify the system works with your real data  
3. **Import Rules** - Configure planning rules based on your current system
4. **Generate Plans** - Create optimized jury assignments for new matches

Would you like me to proceed with adapting the system to work with your existing database structure?
