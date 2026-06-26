# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **multi-project OA system** (FZRBS_OA) with:

| Component | Path | Tech Stack |
|-----------|------|------------|
| Backend API | `yii2.fznews.com.cn/basic/` | Yii2 PHP |
| React Frontend | `mysite/` | React 17 + Ant Design Pro 5 + UmiJS 3.5 |
| Vue Frontend | `vue-project/` | Vue 3.2 + Vite 4 + Pinia + Vant 4 |

## Development Commands

### mysite (React/UmiJS)
```bash
cd mysite
npm run start:dev    # Start dev server (REACT_APP_ENV=dev)
npm run build        # Production build
npm run test         # Jest tests
npm run test:component ./src/components  # Test specific component
npm run lint         # Full lint (JS + style + prettier + tsc)
npm run lint:fix     # Auto-fix lint errors
npm run analyze      # Bundle analyzer
```

### vue-project (Vue 3/Vite)
```bash
cd vue-project
npm run dev          # Start dev server
npm run build        # Production build
npm run preview      # Preview production build
npm run test:unit    # Vitest unit tests
npm run test:e2e     # Cypress e2e tests
npm run test:e2e:dev # Cypress dev mode
npm run lint         # ESLint + Prettier
npm run type-check   # vue-tsc type checking
```

### yii2 Backend
```bash
cd yii2.fznews.com.cn/basic
php yii <command>    # Run Yii console commands
```

## Architecture

### System Module Mapping

Each business module spans both backend and frontend. The mysite React frontend is the **primary** interface for most modules; vue-project provides a mobile-first alternative.

| Module | Backend Controller | Frontend (mysite) | Frontend (vue-project) |
|--------|--------------------|--------------------|------------------------|
| 广告 (Advertising) | `AdvertisemanangeController.php` | `pages/finance/order/` | — |
| 非报 (Budget) | `BudgetController.php` | `pages/finance/budget/` | `views/budget/` |
| 开票 (Invoice) | `InvoicingController.php` | `pages/finance/invoice/` | `views/invoice/` |
| 用印 (Seal Approval) | `QyusesealController.php` | — | `views/useseal/` |
| 延迟 (Delay Approval) | `QypressController.php` | — | `views/press/` |
| 付款 (Payment Approval) | `QyfinanceController.php` | — | `views/finance/` |
| 合同 (Contract) | `ContractController.php` | `pages/finance/contract/` | `views/contract/` |
| 考勤 (Attendance) | `AttendanceController.php` | `pages/finance/attandance/` | `views/attendance/` |
| 摄影派发 (Photo Dispatch) | `PhotodispatchController.php` | — | `views/photodispatch/` |
| 稿费评分 (Manuscript Scoring) | `ManuscriptscoringController.php` | — | `views/manuscriptscoring/` |

### Finance Module Structure (mysite/pages/finance/)

Each subdirectory is a self-contained feature module:
- `budget/` — 非报业务系统: 项目管理、预算/决算、入账、统计、流程配置、字典管理
- `contract/` — 合同系统: 合同CRUD、台账、付款条件、发票关联、锁定/作废
- `invoice/` — 开票系统: 开票申请、发票明细、PDF发票、同步
- `order/` — 广告订单: 订单管理、刊例价、广告审批
- `attandance/` — 考勤异常: 异常打卡、审批单、统计导出
- `useseal/` — 用印审批: 审批单查询、导出、撤销
- `Flowtemplate/` — 流程模板: 财务/薪酬考核/用印/考勤模板、打印位置
- `company/` — 公司/银行账户管理
- `department/` — 部门管理
- `role/` — 角色权限管理

### Backend Structure (yii2.fznews.com.cn/basic/)
- `modules/api/controllers/` — API endpoint controllers. Naming: `{Module}Controller.php` → route `/api/{module}/...`
- `modules/api/models/` — API-specific ActiveRecord models
- `modules/api/commons/` — Shared API utilities (helpers, constants)
- `models/` — Yii2 ActiveRecord models (db schema)
- `config/` — Application config: `web.php` (main), `db.php` (database), `routes.php` (URL rules)
- `web/` — Public web root

### Frontend Structure (mysite/)
- `src/pages/finance/` — Page components (file-based routing via UmiJS)
- `src/services/` — API service layer (UmiJS `request` wrapper)
- `src/components/` — Shared components
- `src/utils/` — Utility functions
- `config/config.ts` — UmiJS configuration (basePath, proxy, openAPI, esbuild, antd theme)
- `config/proxy.ts` — Dev/test/pre proxy configurations

### Frontend Structure (vue-project/)
- `src/views/` — Vue page views organized by module
- `src/api/` — Axios-based API client layer
- `src/stores/` — Pinia state stores (persisted via `pinia-plugin-persist`)
- `src/utils/request.ts` — AES-encrypted API client
- `src/router/index.ts` — Vue Router with 60+ routes

## Key Patterns

### API Convention
- Backend controllers map to API routes: `BudgetController` → `/api/budget/*`
- mysite service layer: `src/pages/finance/{module}/service.ts` — exports async functions using `request()`
- vue-project API layer: `src/api/{module}.ts` — Axios calls with interceptors

### mysite Component Patterns
- Uses `@ant-design/pro-components` ProTable for data tables with built-in pagination/sorting
- DepartmentTreeSelect component for department hierarchy selection
- ProTable columns defined as `ProColumns[]` with `dataIndex`, `key`, `render`, etc.
- Modal-based detail views; form submissions via service layer

### Workflow/Approval Pattern
- `startflow()` — initiates approval workflow
- `flowact()` — approves/rejects/commits action
- `viewflow()` / `getflowinfo()` — retrieves workflow state and history
- Flow templates stored in `Flowtemplate/` module

### Important Notes
- **Node.js**: All mysite scripts use `NODE_OPTIONS=--openssl-legacy-provider` (legacy OpenSSL for Node 17+ compatibility)
- **MFSU disabled**: `config/config.ts` has `mfsu: false` — dev rebuilds are slower
- **Hardcoded secrets**: Backend has credentials in `config/db.php`, `config/web.php`; vue-project has AES keys in `src/utils/aes.ts` — do not expose
- **moment vs dayjs**: mysite uses deprecated `moment`; vue-project correctly uses `dayjs`
- **Bundle size**: Heavy packages (xlsx, video.js, pdfjs-dist) are bundled; no lazy loading visible in mysite routes