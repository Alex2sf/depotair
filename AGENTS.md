# ForgeGuard Engineering Rules — HydroExpert Laravel

For all non-trivial tasks and code changes, strictly follow `forgeguard-engineering`:
**Inspect → Design → Implement → Test → Review → Verify**.

---

## 1. General ForgeGuard Discipline (AI Coding Agent Rules)
- **Work Surgically**: Make every changed line trace directly to the objective. Do not refactor adjacent code, change unrelated styles, or remove pre-existing dead code.
- **Reject AI Slop**: No speculative abstractions, duplicate helpers, mock placeholders in production code, decorative comments, or unverified claims.
- **Deterministic Quality Gates**: Always run `forgeguard gate --changed --output compact` before reporting completion.
- **No Swallowed Errors (FG-ERR-001)**: Never write empty `catch` blocks that hide transaction or network failures.
- **No Hardcoded Credentials (FG-SEC-001)**: Never hardcode WhatsApp/Fonnte API keys, database credentials, or tokens in source code. Always use `config()` and `.env`.

---

## 2. HydroExpert Architecture & Financial Guardrails
- **Dual Cash Standard**:
  - `current_drawer_balance` (Kasir / Uang Laci Fisik: `cash_balances.CASHIER`).
  - `current_owner_balance` (Kas Besar / Owner / Bank: `cash_balances.MAIN`).
- **Audit Trail Immutability**:
  - NEVER modify `balance` directly in `cash_balances` without creating a corresponding log in `cash_transactions` or `cashier_purchases`.
  - Every financial mutation MUST be wrapped inside `DB::transaction(function () { ... })`.
  - Validate non-negative balance: Cashier cannot deposit or record expenses exceeding the current drawer balance.
- **Cash Separation**:
  - Drawer cash strictly reflects physical banknotes and coins in the cashier drawer.
  - Digital payments (QRIS, Transfer Bank, Corporate invoice) belong directly to owner ledger/bank accounts and must NOT enter the cashier drawer balance.

---

## 3. Role-Based Access Control (RBAC)
- Valid roles: `admin`, `kasir`, `owner`, `kurir`, and `super_admin`.
- In `routes/api.php`, ensure role middleware is scoped correctly:
  - Cashier & shift monitoring endpoints (`/shifts/deposits`, `/shifts/history`, `/cash/dashboard`) must permit `role:admin,kasir,owner,super_admin`.
  - Owner dashboard and transactions (`/owner/*`) must be accessible to `owner` and `admin`.

---

## 4. Database & Query Performance (Anti N+1 — FG-DB-001)
- Never perform Eloquent or SQL queries inside iterative loops (`foreach`, `for`, `map`).
- Always eager-load relationships: e.g. `Order::with(['customer', 'products'])`, `CashTransaction::with(['recordedBy', 'onBehalfOf'])`.
- Use pagination (`paginate(...)`) or chunking for lists and reporting queries.

---

## 5. File Uploads & Proof Photos (FG-SEC-007)
- Proof of deposit images must be stored on disk `public` under `deposits/`.
- File resolution MUST use `CashTransaction::getProofImageUrlAttribute()` which handles `public_path()`, `public/storage/`, and `Storage::disk('public')->url()`.
- Prevent path traversal attacks when resolving or handling filenames.

---

## 6. External Integrations (WhatsApp Fonnte — FG-NET-001)
- When executing WhatsApp blasts, batch or rate-limit outgoing requests with proper timeout and exception handling (`try-catch` with logging).
- Provide fallbacks (e.g. manual `https://wa.me/...` links) if the WhatsApp gateway server is unreachable.

