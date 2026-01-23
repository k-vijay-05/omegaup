# Report Tables in omegaUp - Complete Comparison

Yes! There are **separate tables** for different types of reports/nominations in omegaUp. Each serves a different purpose and has its own workflow.

---

## 📊 All Report-Related Tables

### 1️⃣ `Problem_Discussion_Reports` - Discussion Comment Reports

**Purpose**: Users report inappropriate or problematic comments in problem discussions.

**Table Structure**:
```sql
CREATE TABLE `Problem_Discussion_Reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,
  `discussion_id` int NOT NULL,                    -- Which discussion comment was reported
  `identity_id` int NOT NULL,                      -- Who reported it
  `reason` text,                                   -- Why it was reported
  `status` enum('open','resolved','dismissed'),   -- Report status
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  FOREIGN KEY (`discussion_id`) REFERENCES `Problem_Discussions` (`discussion_id`) ON DELETE CASCADE,
  FOREIGN KEY (`identity_id`) REFERENCES `Identities` (`identity_id`) ON DELETE CASCADE
)
```

**Workflow**:
- User reports a discussion comment → Status: `'open'`
- Admin/Reviewer reviews → Status: `'resolved'` (action taken) or `'dismissed'` (invalid report)
- **Reviewers**: System Admins OR `omegaup:discussion-reviewer` group (ACL ID 10)

**Key Features**:
- ✅ Simple reason text
- ✅ One report per user per discussion (prevented by index)
- ✅ Cascade delete with discussion
- ✅ Status-based admin queue

---

### 2️⃣ `QualityNominations` - Problem Quality Reports/Nominations

**Purpose**: Users nominate problems for promotion/demotion or suggest improvements to problem quality.

**Table Structure**:
```sql
CREATE TABLE `QualityNominations` (
  `qualitynomination_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,                          -- Who nominated
  `problem_id` int NOT NULL,                       -- Which problem
  `nomination` enum(
    'suggestion',      -- Suggestion for improvement
    'quality_tag',     -- Tag quality issue
    'promotion',       -- Nominate for promotion
    'demotion',        -- Nominate for demotion
    'dismissal'        -- Dismiss quality concerns
  ) NOT NULL DEFAULT 'suggestion',
  `contents` text NOT NULL,                        -- JSON blob with detailed content
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('open','warning','resolved','banned') NOT NULL DEFAULT 'open',
  PRIMARY KEY (`qualitynomination_id`),
  FOREIGN KEY (`problem_id`) REFERENCES `Problems` (`problem_id`),
  FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`)
)
```

**Related Tables**:
- `QualityNomination_Reviewers` - Assigns reviewers to nominations
- `QualityNomination_Comments` - Comments/votes from reviewers
- `QualityNomination_Log` - Audit log of changes

**Workflow**:
- User creates nomination → Status: `'open'`
- System assigns reviewers (from `omegaup:quality-reviewer` group)
- Reviewers comment/vote → Status changes to `'resolved'`, `'warning'`, or `'banned'`
- **Reviewers**: `omegaup:quality-reviewer` group (ACL ID 3)

**Key Features**:
- ✅ Multiple nomination types (suggestion, promotion, demotion, etc.)
- ✅ Rich JSON content (can include difficulty ratings, tags, rationale, etc.)
- ✅ Reviewer assignment system
- ✅ Comment/voting system for reviewers
- ✅ More complex workflow than discussion reports

---

### 3️⃣ Contest Reports - Dynamic Scoreboard Reports

**Purpose**: Contest admins generate reports from contest scoreboard data.

**Storage**: **NO separate table** - Reports are generated dynamically from:
- `Contests` table
- `Runs` table (submissions)
- `Scoreboard` calculations

**Workflow**:
- Contest admin requests report
- System queries scoreboard data dynamically
- Generates report on-the-fly (CSV/PDF export)
- **Access**: Contest admins only (via ACL)

**Key Features**:
- ✅ No persistent storage (generated on-demand)
- ✅ Based on real-time scoreboard data
- ✅ Multiple formats (CSV, PDF, etc.)
- ✅ Contest-specific access control

---

## 🔄 Comparison Table

| Feature | `Problem_Discussion_Reports` | `QualityNominations` | Contest Reports |
|---------|------------------------------|---------------------|-----------------|
| **What's Reported** | Discussion comments | Problems (quality) | Contest participants |
| **Who Reports** | Any logged-in user | Any logged-in user | Contest admins only |
| **Who Reviews** | System admins OR discussion-reviewers | Quality reviewers | Contest admins |
| **Storage** | Persistent table | Persistent table | Dynamic (no table) |
| **Status Values** | `open`, `resolved`, `dismissed` | `open`, `warning`, `resolved`, `banned` | N/A |
| **Content Type** | Simple text reason | Rich JSON blob | Scoreboard data |
| **Review System** | Simple (approve/dismiss) | Complex (assign reviewers, comments, votes) | Export only |
| **Related Tables** | None | `QualityNomination_Reviewers`, `QualityNomination_Comments`, `QualityNomination_Log` | None |
| **Cascade Delete** | Yes (with discussion) | No (kept for audit) | N/A |
| **ACL Group** | `omegaup:discussion-reviewer` (ID 10) | `omegaup:quality-reviewer` (ID 3) | Contest ACL |

---

## 📋 Design Patterns

### Pattern 1: Simple Report Table (Discussion Reports)
```
User → Report → Admin Queue → Resolve/Dismiss
```
- Simple status tracking
- Direct admin action
- Minimal metadata

### Pattern 2: Complex Nomination System (Quality Nominations)
```
User → Nomination → Assign Reviewers → Reviewers Comment/Vote → Resolve
```
- Multiple statuses
- Reviewer assignment
- Comment/voting system
- Audit logging

### Pattern 3: Dynamic Reports (Contest Reports)
```
Admin → Request Report → Query Data → Generate Export
```
- No persistent storage
- Real-time data
- Export formats

---

## 🎯 Why Separate Tables?

Each table serves a **different domain**:

1. **`Problem_Discussion_Reports`**: 
   - **Domain**: Content moderation
   - **Focus**: Inappropriate comments
   - **Action**: Delete/keep comment

2. **`QualityNominations`**:
   - **Domain**: Problem quality management
   - **Focus**: Problem improvement/promotion
   - **Action**: Change problem visibility/tags/quality

3. **Contest Reports**:
   - **Domain**: Contest analytics
   - **Focus**: Performance metrics
   - **Action**: Export/analysis only

---

## 🔐 Access Control Summary

| Report Type | Who Can Report | Who Can Review |
|------------|----------------|----------------|
| Discussion Reports | Any logged-in user | System Admins OR `omegaup:discussion-reviewer` |
| Quality Nominations | Any logged-in user | `omegaup:quality-reviewer` |
| Contest Reports | Contest Admins | Contest Admins |

---

## 📝 Summary

✅ **Yes, separate tables exist for different report types:**
- `Problem_Discussion_Reports` - For discussion comment moderation
- `QualityNominations` - For problem quality management
- Contest Reports - Dynamic (no table, generated from scoreboard)

Each follows a different pattern based on its specific needs:
- **Simple** (discussion reports) - Quick moderation
- **Complex** (quality nominations) - Multi-step review process
- **Dynamic** (contest reports) - On-demand generation

The `Problem_Discussion_Reports` table follows the **simple pattern**, similar to how `QualityNominations` started but evolved into a more complex system.

