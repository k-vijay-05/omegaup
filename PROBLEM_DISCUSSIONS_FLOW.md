# Problem Discussions System - Complete Flow & Table Structure

## 📊 Database Tables Overview

The Problem Discussions system consists of **4 main tables** that work together to enable discussions, replies, voting, and reporting functionality for problems.

---

## 1️⃣ `Problem_Discussions` - Main Discussion Comments

**Purpose**: Stores the main/top-level comments posted on a problem's discussion tab.

### Table Structure:
```sql
CREATE TABLE `Problem_Discussions` (
  `discussion_id` int NOT NULL AUTO_INCREMENT,           -- Primary key
  `problem_id` int NOT NULL,                             -- Which problem this discussion belongs to
  `identity_id` int NOT NULL,                            -- Who created the comment
  `content` mediumtext NOT NULL,                         -- Comment content (markdown format)
  `upvotes` int NOT NULL DEFAULT '0',                    -- Cached count of upvotes
  `downvotes` int NOT NULL DEFAULT '0',                  -- Cached count of downvotes
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`discussion_id`),
  KEY `idx_problem_id` (`problem_id`),                  -- Fast lookup by problem
  KEY `idx_identity_id` (`identity_id`),                -- Fast lookup by author
  KEY `idx_created_at` (`created_at`),                  -- Sorting by date
  KEY `idx_upvotes` (`upvotes`),                        -- Sorting by popularity
  CONSTRAINT `fk_pd_problem_id` FOREIGN KEY (`problem_id`) REFERENCES `Problems` (`problem_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pd_identity_id` FOREIGN KEY (`identity_id`) REFERENCES `Identities` (`identity_id`) ON DELETE CASCADE
)
```

### Key Features:
- **One-to-Many**: One problem can have many discussions
- **Cascade Delete**: If problem or user is deleted, discussions are automatically deleted
- **Vote Counts**: Stores cached upvote/downvote counts (updated from `Problem_Discussion_Votes`)

---

## 2️⃣ `Problem_Discussion_Replies` - Replies to Comments

**Purpose**: Stores replies/responses to main discussion comments (threaded comments).

### Table Structure:
```sql
CREATE TABLE `Problem_Discussion_Replies` (
  `reply_id` int NOT NULL AUTO_INCREMENT,               -- Primary key
  `discussion_id` int NOT NULL,                          -- Which discussion this replies to
  `identity_id` int NOT NULL,                            -- Who created the reply
  `content` mediumtext NOT NULL,                         -- Reply content (markdown format)
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reply_id`),
  KEY `idx_discussion_id` (`discussion_id`),            -- Fast lookup by parent discussion
  KEY `idx_identity_id` (`identity_id`),                 -- Fast lookup by author
  KEY `idx_created_at` (`created_at`),                  -- Sorting by date
  CONSTRAINT `fk_pdr_discussion_id` FOREIGN KEY (`discussion_id`) REFERENCES `Problem_Discussions` (`discussion_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pdr_identity_id` FOREIGN KEY (`identity_id`) REFERENCES `Identities` (`identity_id`) ON DELETE CASCADE
)
```

### Key Features:
- **Many-to-One**: Many replies can belong to one discussion
- **Cascade Delete**: If parent discussion is deleted, all replies are automatically deleted
- **No Voting**: Replies don't have voting (only main discussions do)

---

## 3️⃣ `Problem_Discussion_Votes` - Individual Votes

**Purpose**: Tracks each individual upvote/downvote on discussions (prevents duplicate votes).

### Table Structure:
```sql
CREATE TABLE `Problem_Discussion_Votes` (
  `vote_id` int NOT NULL AUTO_INCREMENT,                -- Primary key
  `discussion_id` int NOT NULL,                          -- Which discussion was voted
  `identity_id` int NOT NULL,                            -- Who voted
  `vote_type` enum('upvote','downvote') NOT NULL,       -- Type of vote
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vote_id`),
  UNIQUE KEY `unique_vote` (`discussion_id`,`identity_id`),  -- One vote per user per discussion
  KEY `idx_discussion_id` (`discussion_id`),
  KEY `idx_identity_id` (`identity_id`),
  CONSTRAINT `fk_pdv_discussion_id` FOREIGN KEY (`discussion_id`) REFERENCES `Problem_Discussions` (`discussion_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pdv_identity_id` FOREIGN KEY (`identity_id`) REFERENCES `Identities` (`identity_id`) ON DELETE CASCADE
)
```

### Key Features:
- **Unique Constraint**: One user can only vote once per discussion (`unique_vote`)
- **Vote Switching**: User can change vote type (upvote ↔ downvote) or remove vote
- **Cascade Delete**: Votes deleted when discussion or user is deleted
- **Aggregation**: Vote counts are aggregated and cached in `Problem_Discussions.upvotes`/`downvotes`

---

## 4️⃣ `Problem_Discussion_Reports` - Content Moderation

**Purpose**: Stores reports of inappropriate content for admin review.

### Table Structure:
```sql
CREATE TABLE `Problem_Discussion_Reports` (
  `report_id` int NOT NULL AUTO_INCREMENT,              -- Primary key
  `discussion_id` int NOT NULL,                          -- Which discussion was reported
  `identity_id` int NOT NULL,                            -- Who reported it
  `reason` text,                                         -- Why it was reported
  `status` enum('open','resolved','dismissed') NOT NULL DEFAULT 'open',  -- Report status
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  KEY `idx_discussion_id` (`discussion_id`),            -- Fast lookup by discussion
  KEY `idx_identity_id` (`identity_id`),                -- Fast lookup by reporter
  KEY `idx_status` (`status`),                          -- Filtering open reports (for admin queue)
  KEY `idx_discussion_identity` (`discussion_id`, `identity_id`),  -- Check if user already reported
  CONSTRAINT `fk_pdre_discussion_id` FOREIGN KEY (`discussion_id`) REFERENCES `Problem_Discussions` (`discussion_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pdre_identity_id` FOREIGN KEY (`identity_id`) REFERENCES `Identities` (`identity_id`) ON DELETE CASCADE
)
```

### Key Features:
- **Status Tracking**: Reports start as `'open'`, then become `'resolved'` or `'dismissed'`
- **Duplicate Prevention**: Index on `(discussion_id, identity_id)` helps check if user already reported
- **Admin Queue**: Admins query `WHERE status = 'open' ORDER BY created_at DESC` to see new reports
- **Cascade Delete**: Reports deleted when discussion or reporter is deleted

---

## 🔄 Complete User Flow

### **Flow 1: Creating a Discussion**

```
1. User visits problem page → clicks "Discussion" tab
2. User types comment and clicks "Post"
3. Frontend calls: api.ProblemDiscussion.create({ problem_alias, content })
4. Backend:
   - Validates problem exists
   - Creates row in Problem_Discussions:
     - problem_id = problem.problem_id
     - identity_id = current_user.identity_id
     - content = user's comment
     - upvotes = 0, downvotes = 0
   - Returns discussion_id
5. Frontend refreshes discussion list
```

### **Flow 2: Replying to a Discussion**

```
1. User clicks "Reply" on a discussion comment
2. User types reply and clicks "Post Reply"
3. Frontend calls: api.ProblemDiscussion.createReply({ discussion_id, content })
4. Backend:
   - Validates discussion exists
   - Creates row in Problem_Discussion_Replies:
     - discussion_id = parent discussion
     - identity_id = current_user.identity_id
     - content = reply text
5. Frontend refreshes replies list
```

### **Flow 3: Voting on a Discussion**

```
1. User clicks upvote/downvote button
2. Frontend calls: api.ProblemDiscussion.vote({ discussion_id, vote_type: 'upvote' })
3. Backend:
   a. Checks Problem_Discussion_Votes for existing vote
   b. If no vote exists:
      - Creates row in Problem_Discussion_Votes
      - Recalculates vote counts from all votes
      - Updates Problem_Discussions.upvotes/downvotes
   c. If vote exists:
      - If same type: Remove vote (toggle off)
      - If different type: Update vote_type (switch vote)
      - Recalculate and update counts
4. Returns updated upvotes/downvotes counts
5. Frontend updates UI
```

**Vote Logic Details**:
- First click: Creates vote
- Second click (same type): Removes vote (undo)
- Click different type: Switches vote (upvote → downvote or vice versa)

### **Flow 4: Reporting Inappropriate Content**

```
1. User clicks "Report" on a discussion
2. User enters reason and submits
3. Frontend calls: api.ProblemDiscussion.report({ discussion_id, reason })
4. Backend:
   - Validates discussion exists
   - Checks if user already reported (hasUserReported)
   - If not reported:
     - Creates row in Problem_Discussion_Reports:
       - discussion_id = reported discussion
       - identity_id = reporter's identity_id
       - reason = user's reason
       - status = 'open'
   - Returns report_id
5. Report appears in admin review queue
```

### **Flow 5: Admin Reviewing Reports** (Future Implementation)

```
1. Admin (system admin or discussion-reviewer) visits admin panel
2. Frontend calls: api.ProblemDiscussion.listReports({ page, page_size })
3. Backend:
   - Checks authorization (isSystemAdmin OR isDiscussionReviewer)
   - Queries Problem_Discussion_Reports:
     WHERE status = 'open'
     ORDER BY created_at DESC
     LIMIT page_size OFFSET offset
   - Joins with Problem_Discussions to get discussion content
   - Joins with Identities to get reporter username
   - Returns paginated list
4. Admin sees list of open reports with:
   - Discussion content preview
   - Reporter username
   - Reason for report
   - Created date
5. Admin takes action:
   - "Delete Comment" → Calls api.ProblemDiscussion.resolveReport({ report_id, action: 'delete' })
   - "Dismiss Report" → Calls api.ProblemDiscussion.resolveReport({ report_id, action: 'keep' })
6. Backend:
   - Updates report status to 'resolved' or 'dismissed'
   - Optionally deletes discussion if action = 'delete'
```

---

## 🔗 Table Relationships

```
Problems (1) ──< (Many) Problem_Discussions
                │
                ├──< (Many) Problem_Discussion_Replies
                │
                ├──< (Many) Problem_Discussion_Votes
                │
                └──< (Many) Problem_Discussion_Reports

Identities (1) ──< (Many) Problem_Discussions (as author)
                │
                ├──< (Many) Problem_Discussion_Replies (as author)
                │
                ├──< (Many) Problem_Discussion_Votes (as voter)
                │
                └──< (Many) Problem_Discussion_Reports (as reporter)
```

### **Cascade Delete Behavior**:
- Delete `Problem` → All related discussions, replies, votes, reports deleted
- Delete `Identity` → All their discussions, replies, votes, reports deleted
- Delete `Problem_Discussions` → All replies, votes, reports for that discussion deleted

---

## 📈 Indexes & Performance

### **Optimized Queries**:

1. **List discussions by problem**:
   - Uses: `idx_problem_id` + `idx_created_at` or `idx_upvotes`
   - Query: `SELECT * FROM Problem_Discussions WHERE problem_id = ? ORDER BY created_at DESC`

2. **Get replies for discussion**:
   - Uses: `idx_discussion_id` + `idx_created_at`
   - Query: `SELECT * FROM Problem_Discussion_Replies WHERE discussion_id = ? ORDER BY created_at ASC`

3. **Check if user voted**:
   - Uses: `unique_vote` constraint
   - Query: `SELECT * FROM Problem_Discussion_Votes WHERE discussion_id = ? AND identity_id = ?`

4. **Admin report queue**:
   - Uses: `idx_status` + `idx_created_at` (composite index in migration 00256)
   - Query: `SELECT * FROM Problem_Discussion_Reports WHERE status = 'open' ORDER BY created_at DESC`

5. **Check if user reported**:
   - Uses: `idx_discussion_identity` composite index
   - Query: `SELECT COUNT(*) FROM Problem_Discussion_Reports WHERE discussion_id = ? AND identity_id = ?`

---

## 🔐 Access Control & Permissions

### **User Actions**:
- **Anyone**: Can view discussions and replies
- **Logged-in users**: Can create discussions, replies, vote, report
- **Discussion owner**: Can edit/delete their own discussion
- **Reply owner**: Can edit/delete their own reply

### **Admin Actions** (Discussion Reviewers):
- **System Admins** (`isSystemAdmin`) OR
- **Discussion Reviewers** (`isDiscussionReviewer` - ACL ID 10, Group: `omegaup:discussion-reviewer`)
- Can: View report queue, resolve reports, delete discussions

---

## 📝 Summary

| Table | Purpose | Key Relationships |
|-------|---------|-------------------|
| `Problem_Discussions` | Main comments | Links to Problems, Identities |
| `Problem_Discussion_Replies` | Threaded replies | Links to Problem_Discussions |
| `Problem_Discussion_Votes` | Voting system | Links to Problem_Discussions, prevents duplicates |
| `Problem_Discussion_Reports` | Moderation queue | Links to Problem_Discussions, tracks report status |

**Design Principles**:
- ✅ Normalized structure (no redundant data)
- ✅ Cascade deletes (data integrity)
- ✅ Cached vote counts (performance)
- ✅ Unique constraints (data consistency)
- ✅ Proper indexing (query performance)
- ✅ Status tracking (moderation workflow)

