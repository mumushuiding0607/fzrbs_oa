# Investigation Plan: Why user "chenbinfeng" doesn't get evaluation tasks generated

## Code Analysis Summary

### actionGeneratetasks() Logic Flow (lines 330-440)

1. **Validates input dates** (start_date, end_date) - must be provided
2. **Gets evaluated departments** from `fzrbs_budget_dict`:
   - Query: `FzrbsBudgetDict::find()->where(['type' => '被考评部门', 'label' => '被考评部门'])->asArray()->one()`
   - Extracts `dept` field, splits by comma into `$deptIds`
3. **Gets the "部门考评人" role ID** from `weixin_oa_role`:
   - Query: `WeixinOaRole::find()->where(['rolename' => '部门考评人'])->select('id')->scalar()`
4. **Gets all evaluators with that role**:
   - Query joins `weixin_oa_flowrole` (fr) with `weixin_leave_userinfo` (u)
   - Conditions:
     - `fr.role = roleId`
     - `u.departmentid IN deptIds` (user's dept must be in evaluated dept list)
     - `u.status = 1` (user must be active)
   - Groups by `fr.userid`
5. **For each evaluator**:
   - Filters out their own department from target list
   - Checks if task already exists (year, quarter, scorer_id)
   - Creates new task if not exists

## Required Database Checks

### 1. weixin_leave_userinfo (User Info Table)
Check if user "chenbinfeng" exists and verify:
- userid (string)
- departmentid (int)
- status (should be 1 for active)
- st (should be 1 for normal, 0 for deleted)

### 2. weixin_oa_flowrole (Role Assignment Table)
Check if chenbinfeng has entry with role = "部门考评人" (the role ID obtained from weixin_oa_role):
- role (int - role ID, NOT the name)
- userid (string)

### 3. fzrbs_budget_dict (Department Config)
Get the evaluated departments list:
- type='被考评部门'
- label='被考评部门'
- dept field contains comma-separated dept IDs

### 4. Cross-Reference
Check if chenbinfeng's departmentid is in the evaluated departments list

### 5. fzrbs_evaluation_task (Existing Tasks)
Check if a task already exists for chenbinfeng for the current year/quarter

## Root Cause Hypotheses

For chenbinfeng to be included, ALL of these must be true:
1. User exists in weixin_leave_userinfo with status=1 and st=1
2. User has a record in weixin_oa_flowrole where role matches "部门考评人" role ID
3. User's departmentid is IN the deptIds list from fzrbs_budget_dict

Common failure points:
- User's status != 1 (inactive or not activated)
- User's st != 1 (deleted)
- User not assigned "部门考评人" role in flowrole table
- User's departmentid not in the configured evaluated departments
- Task already exists for current year/quarter

## Query Plan

Run these MySQL queries against database (host: 129.0.98.14, dbname: fzstm):

```sql
-- 1. Check user info
SELECT userid, name, departmentid, departmentname, status, st 
FROM weixin_leave_userinfo 
WHERE name = 'chenbinfeng' OR userid LIKE '%chenbinfeng%';

-- 2. Get role ID
SELECT id FROM weixin_oa_role WHERE rolename = '部门考评人';

-- 3. Check flowrole entry (use the ID from query 2)
SELECT fr.userid, fr.role, fr.username, u.name, u.departmentid 
FROM weixin_oa_flowrole fr
LEFT JOIN weixin_leave_userinfo u ON fr.userid = u.userid
WHERE (fr.userid LIKE '%chenbinfeng%' OR u.name = 'chenbinfeng')
  AND fr.role = [ROLE_ID_FROM_QUERY_2];

-- 4. Get evaluated departments
SELECT * FROM fzrbs_budget_dict WHERE type = '被考评部门' AND label = '被考评部门';

-- 5. Check existing tasks
SELECT * FROM fzrbs_evaluation_task 
WHERE scorer_id LIKE '%chenbinfeng%' OR scorer_id IN (
  SELECT userid FROM weixin_leave_userinfo WHERE name = 'chenbinfeng'
);
```

## Expected Output
After running queries, report:
- Whether user exists and their status/st
- Whether user has the required role
- What departments are configured
- Whether user's dept is in the list
- Whether tasks already exist

