-- EazyPOS: export all Supabase data as one JSON document.
--
-- 1. Supabase dashboard > SQL Editor > New query.
-- 2. Paste this whole file and click Run.
-- 3. Copy the single "eazypos_export" cell value into a file, e.g.
--    storage/app/private/supabase-export.json
-- 4. php artisan eazypos:import-supabase storage/app/private/supabase-export.json
--
-- Password hashes are included so every user keeps their current password.
-- Keep the exported file private and delete it after the import.

select json_build_object(
  'users', (select coalesce(json_agg(u), '[]'::json) from (
      select id, email, encrypted_password, email_confirmed_at, raw_user_meta_data,
             last_sign_in_at, created_at, updated_at
      from auth.users
  ) u),
  'businesses',            (select coalesce(json_agg(t), '[]'::json) from public.businesses t),
  'business_users',        (select coalesce(json_agg(t), '[]'::json) from public.business_users t),
  'staff_invitations',     (select coalesce(json_agg(t), '[]'::json) from public.staff_invitations t),
  'document_sequences',    (select coalesce(json_agg(t), '[]'::json) from public.document_sequences t),
  'categories',            (select coalesce(json_agg(t), '[]'::json) from public.categories t),
  'brands',                (select coalesce(json_agg(t), '[]'::json) from public.brands t),
  'units',                 (select coalesce(json_agg(t), '[]'::json) from public.units t),
  'products',              (select coalesce(json_agg(t), '[]'::json) from public.products t),
  'customers',             (select coalesce(json_agg(t), '[]'::json) from public.customers t),
  'suppliers',             (select coalesce(json_agg(t), '[]'::json) from public.suppliers t),
  'cash_registers',        (select coalesce(json_agg(t), '[]'::json) from public.cash_registers t),
  'register_transactions', (select coalesce(json_agg(t), '[]'::json) from public.register_transactions t),
  'sales',                 (select coalesce(json_agg(t), '[]'::json) from public.sales t),
  'sale_items',            (select coalesce(json_agg(t), '[]'::json) from public.sale_items t),
  'sale_payments',         (select coalesce(json_agg(t), '[]'::json) from public.sale_payments t),
  'invoices',              (select coalesce(json_agg(t), '[]'::json) from public.invoices t),
  'sale_returns',          (select coalesce(json_agg(t), '[]'::json) from public.sale_returns t),
  'sale_return_items',     (select coalesce(json_agg(t), '[]'::json) from public.sale_return_items t),
  'purchases',             (select coalesce(json_agg(t), '[]'::json) from public.purchases t),
  'purchase_items',        (select coalesce(json_agg(t), '[]'::json) from public.purchase_items t),
  'purchase_returns',      (select coalesce(json_agg(t), '[]'::json) from public.purchase_returns t),
  'purchase_return_items', (select coalesce(json_agg(t), '[]'::json) from public.purchase_return_items t),
  'expense_categories',    (select coalesce(json_agg(t), '[]'::json) from public.expense_categories t),
  'expenses',              (select coalesce(json_agg(t), '[]'::json) from public.expenses t),
  'stock_movements',       (select coalesce(json_agg(t), '[]'::json) from public.stock_movements t),
  'quotations',            (select coalesce(json_agg(t), '[]'::json) from public.quotations t),
  'quotation_items',       (select coalesce(json_agg(t), '[]'::json) from public.quotation_items t),
  'shipments',             (select coalesce(json_agg(t), '[]'::json) from public.shipments t)
) as eazypos_export;
