# 项目维护 README - FZRBS_OA 系统

> 本文件记录项目结构信息，供 AI 理解和维护系统使用

---

## 项目概览

| 项目 | 路径 |
|------|------|
| 后端 | E:\Workspaces\fzrbs_oa\yii2.fznews.com.cn\basic\modules\api\controllers |
| 前端(mysite) | E:\Workspaces\fzrbs_oa\mysite\src\pages\finance |
| 前端(vue-project) | E:\Workspaces\fzrbs_oa\vue-project\src\views |

---

## 系统模块对照表

| 系统 | 后端 | 前端目录1 (mysite) | 前端目录2 (vue-project) |
|------|------|-------------------|------------------------|
| 广告系统 | AdvertisemanangeController.php | pages/finance/order | (暂无) |
| 非报系统 | BudgetController.php | pages/finance/budget | views/budget |
| 开票系统 | InvoicingController.php | pages/finance/invoice | views/invoice |
| 用印审批 | QyusesealController.php | - | views/useseal |
| 延迟审批 | QypressController.php | - | views/press |
| 付款审批 | QyfinanceController.php | - | views/finance |
| 合同系统 | ContractController.php | pages/finance/contract | views/contract |

---

## 广告系统 (order)

### mysite 前端 (pages/finance/order/)
| 文件 | 用途 |
|------|------|
| AddAdvitem.tsx | 添加广告项 |
| AddAdvsize.tsx | 添加广告规格 |
| AddFzAdv.tsx | 添加投放单 |
| AddOrder.tsx | 添加订单 |
| AddPrice.tsx | 添加价格 |
| AddSmallBusiness.tsx | 添加小额广告业务 |
| AdvitemList.tsx | 广告项列表 |
| advsize.tsx | 规格管理 |
| EditOrder.tsx | 编辑订单 |
| OrderList.tsx | 订单列表 |

---

## 非报系统 (budget)
- mysite: pages/finance/budget/
- vue-project: views/budget/
- 后端: BudgetController.php

---

## 开票系统 (invoice)
- mysite: pages/finance/invoice/
- vue-project: views/invoice/
- 后端: InvoicingController.php

---

## 用印审批系统 (useseal)
- vue-project: views/useseal/
- 后端: QyusesealController.php

---

## 延迟审批系统 (press)
- vue-project: views/press/
- 后端: QypressController.php

---

## 付款审批系统 (finance)
- vue-project: views/finance/
- 后端: QyfinanceController.php

---

## 合同系统 (contract)
- mysite: pages/finance/contract/
- vue-project: views/contract/
- 后端: ContractController.php

---

## 技术栈

- 前端: React (mysite), Vue 2 (vue-project)
- 后端: Yii2 PHP

---

## 更新日志

- 2026-04-09: 初始创建
- 2026-04-09 11:34: 根据用户反馈更新