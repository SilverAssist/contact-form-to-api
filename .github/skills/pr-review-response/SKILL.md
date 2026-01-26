---
name: pr-review-response
description: Respond to GitHub Pull Request review comments. Use when addressing reviewer feedback, replying to review threads, or making fixes based on PR review. IMPORTANT - Reviews are different from PR comments.
---

# PR Review Response Skill

## When to Use

- Addressing reviewer feedback on a PR
- Need to reply to review comments/threads
- Making fixes based on PR review and confirming resolution

## ⚠️ CRITICAL: Reviews vs Comments

**Reviews** and **Comments** are DIFFERENT things in GitHub:

| Type | Location | How to Get | How to Reply |
|------|----------|------------|--------------|
| **Reviews** | Inline on specific file lines/code | MCP `pull_request_read` or GraphQL | `gh api graphql` mutation |
| **Comments** | PR conversation tab (general) | MCP `get_issue_comments` | MCP `add_issue_comment` or `gh issue comment` |

### Reviews (This Skill)
- Made directly on modified files
- Reference specific lines of code
- Request changes to specific code sections
- Appear in "Files changed" tab
- Have thread IDs starting with `PRRT_`

### Comments (NOT This Skill)
- General discussion on the PR
- Appear in "Conversation" tab
- Don't reference specific lines

## CRITICAL Rules

1. **Each review thread gets an INDIVIDUAL response** - NO summary comments
2. **Always use `| cat`** on all `gh` commands to prevent pager hang
3. **Reference specific commits** when describing fixes
4. **MCP cannot reply to review threads** - Must use `gh api graphql`

## Workflow

### Step 1: Get Review Comments

**Option A: Using MCP (Recommended for reading)**

Use the `pull_request_read` tool to get review comments. The MCP will return review threads with their IDs.

**Option B: Using GraphQL directly**

```bash
gh api graphql -f query='
query {
  repository(owner: "SilverAssist", name: "contact-form-to-api") {
    pullRequest(number: PR_NUMBER) {
      reviewThreads(first: 50) {
        nodes {
          id
          path
          line
          isResolved
          comments(first: 5) {
            nodes {
              id
              body
              author { login }
            }
          }
        }
      }
    }
  }
}' | cat
```

### Step 2: Analyze Each Review Comment

For each thread, determine:

- **Valid issue**: Make code fix, note what changed
- **Already correct**: Prepare explanation
- **Won't fix**: Prepare justification

### Step 3: Make Fixes and Commit

```bash
# Make all code changes for ALL review comments
git add -A
git commit -m "fix: Address PR review comments

- Fix for review comment 1
- Fix for review comment 2
- ..."
git push
```

**Get the commit SHA for responses:**

```bash
git rev-parse --short HEAD
```

### Step 4: Reply to EACH Thread Individually

⚠️ **MCP CANNOT reply to review threads** - Must use GraphQL mutations.

```bash
# Reply to thread 1
gh api graphql -f query='
mutation {
  addPullRequestReviewThreadReply(input: {
    pullRequestReviewThreadId: "PRRT_kwDONqY9Pc6XXXXX",
    body: "Applied in commit abc1234..."
  }) {
    comment { id }
  }
}' | cat

# Reply to thread 2 (SEPARATE mutation for each thread)
gh api graphql -f query='
mutation {
  addPullRequestReviewThreadReply(input: {
    pullRequestReviewThreadId: "PRRT_kwDONqY9Pc6YYYYY",
    body: "Applied in commit abc1234..."
  }) {
    comment { id }
  }
}' | cat
```

## Response Format (MANDATORY)

### For Applied Fixes

```markdown
Applied in commit [abc1234](https://github.com/SilverAssist/contact-form-to-api/commit/abc1234). **Short description of the fix.**

**Changes:**
- Specific change 1
- Specific change 2
- Specific change 3

General explanation of what was done and why it addresses the review comment.
```

**Example:**

```markdown
Applied in commit [d4d91d2](https://github.com/SilverAssist/contact-form-to-api/commit/d4d91d2). **Implemented proper MVC architecture.**

**Changes:**
- Controller (LogsController) now fetches forms with logs from service layer
- View (RequestLogView) receives data from controller and passes to partial
- Partial (DateFilterPartial) is now pure presentation - no service instantiation

This follows the MVC pattern correctly: Controllers handle data orchestration, Views coordinate presentation, and Partials render UI components.
```

### For Already Correct Code

```markdown
**Already correct.** Explanation of why current implementation is correct.

The code does X because Y, which is the intended behavior for Z.
```

### For Won't Fix

```markdown
**Won't fix.** Justification for keeping current implementation.

This approach was chosen because... Alternative would cause...
```

## Complete Example Flow

```bash
# 1. Read PR to get review comments (use MCP or GraphQL)
# MCP: activate_pull_request_review_tools → pull_request_read

# 2. Make fixes based on reviews
# ... edit files ...

# 3. Commit and push
git add -A
git commit -m "fix: Address PR #76 review comments

- Remove form data from email alerts (privacy)
- Add spam prevention with transient flag
- Fix MVC violation in EmailAlertService"
git push

# 4. Get commit SHA
COMMIT_SHA=$(git rev-parse --short HEAD)
echo $COMMIT_SHA  # e.g., d4d91d2

# 5. Reply to each review thread
gh api graphql -f query='
mutation {
  addPullRequestReviewThreadReply(input: {
    pullRequestReviewThreadId: "PRRT_kwDONqY9Pc6XXXXX",
    body: "Applied in commit [d4d91d2](https://github.com/SilverAssist/contact-form-to-api/commit/d4d91d2). **Removed form data from email alerts.**\n\n**Changes:**\n- Removed `alert_include_form_data` setting entirely\n- Emails now only include error information, no PII\n- Deleted UI checkbox and related code\n\nThis addresses the privacy concern - emails are now focused purely on error notification."
  }) {
    comment { id }
  }
}' | cat
```

## Escaping in GraphQL Body

When the response contains special characters, escape them:

- Newlines: `\n`
- Quotes: `\"`
- Backslashes: `\\`
- Asterisks for bold: `**text**` (works as-is)
- Links: `[text](url)` (works as-is)

## Important Notes

- Thread IDs start with `PRRT_` (Pull Request Review Thread)
- Each thread needs a SEPARATE GraphQL mutation
- Always verify responses were posted with `gh pr view PR_NUMBER | cat`
- Commit links format: `https://github.com/SilverAssist/contact-form-to-api/commit/SHA`
