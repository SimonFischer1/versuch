-- NavTool · Paywall (einmal im Supabase SQL Editor ausführen, NACH schema.sql und admin.sql)
-- Alles Wichtige wird in der Datenbank entschieden, nicht im Browser:
--  * Codes liegen in einer Tabelle, auf die Nutzer keinen direkten Zugriff haben
--  * Einlösen nur über redeem_code() (einmalig, Sperre nach 5 Fehlversuchen in 15 Minuten)
--  * Gratis-Konten dürfen nur EINE Reise besitzen (Trigger, nicht umgehbar)

create sequence if not exists public.premium_order_seq start 1001;

create table if not exists public.premium_codes (
  code text primary key,
  order_no text not null,
  customer_name text not null,
  customer_email text,
  expires_on date,                       -- null = unbegrenzt; sonst Zugriff bis einschließlich 23:59:59 (Europe/Berlin)
  created_at timestamptz not null default now(),
  revoked boolean not null default false,
  redeemed_by uuid references auth.users(id) on delete set null,
  redeemed_at timestamptz
);
create table if not exists public.premium_access (
  user_id uuid primary key references auth.users(id) on delete cascade,
  code text,
  valid_until timestamptz,               -- null = unbegrenzt
  granted_at timestamptz not null default now()
);
create table if not exists public.premium_attempts (
  id bigserial primary key,
  user_id uuid not null,
  at timestamptz not null default now()
);
alter table public.premium_codes enable row level security;
alter table public.premium_access enable row level security;
alter table public.premium_attempts enable row level security;
revoke all on public.premium_codes, public.premium_access, public.premium_attempts from anon, authenticated;

create or replace function public.has_premium(uid uuid) returns boolean
language sql security definer stable set search_path = public as $$
  select exists (select 1 from public.premium_access a where a.user_id = uid and (a.valid_until is null or a.valid_until > now())) $$;
revoke execute on function public.has_premium(uuid) from public, anon, authenticated;

-- Eigener Status (für die Website)
create or replace function public.my_plan() returns jsonb
language sql security definer stable set search_path = public as $$
  select coalesce((select jsonb_build_object('premium', (a.valid_until is null or a.valid_until > now()), 'valid_until', a.valid_until)
                   from public.premium_access a where a.user_id = auth.uid()),
                  jsonb_build_object('premium', false, 'valid_until', null)) $$;
revoke execute on function public.my_plan() from public, anon;
grant execute on function public.my_plan() to authenticated;

-- Code einlösen (einmalig)
create or replace function public.redeem_code(p_code text) returns jsonb
language plpgsql security definer set search_path = public as $$
declare c public.premium_codes; vu timestamptz; cur timestamptz; has boolean := false;
begin
  if auth.uid() is null then raise exception 'Bitte zuerst anmelden' using errcode = '42501'; end if;
  if (select count(*) from public.premium_attempts where user_id = auth.uid() and at > now() - interval '15 minutes') >= 5 then
    raise exception 'Zu viele Fehlversuche. Bitte in 15 Minuten erneut versuchen.' using errcode = '42501'; end if;
  select * into c from public.premium_codes where code = upper(btrim(p_code)) for update;
  if not found or c.revoked or c.redeemed_by is not null
     or (c.expires_on is not null and now() >= ((c.expires_on + 1)::timestamp at time zone 'Europe/Berlin')) then
    insert into public.premium_attempts(user_id) values (auth.uid());
    raise exception 'Code ungültig, abgelaufen oder bereits verwendet';
  end if;
  vu := case when c.expires_on is null then null else ((c.expires_on + 1)::timestamp at time zone 'Europe/Berlin') end;
  select a.valid_until, true into cur, has from public.premium_access a where a.user_id = auth.uid();
  if has and cur is null then vu := null;                       -- schon unbegrenzt: bleibt so
  elsif has and vu is not null and cur > vu then vu := cur; end if;
  insert into public.premium_access(user_id, code, valid_until) values (auth.uid(), c.code, vu)
    on conflict (user_id) do update set code = excluded.code, valid_until = excluded.valid_until, granted_at = now();
  update public.premium_codes set redeemed_by = auth.uid(), redeemed_at = now() where code = c.code;
  return jsonb_build_object('premium', true, 'valid_until', vu);
end $$;
revoke execute on function public.redeem_code(text) from public, anon;
grant execute on function public.redeem_code(text) to authenticated;

-- Gratis-Konten: höchstens eine Reise
create or replace function public.enforce_trip_limit() returns trigger
language plpgsql security definer set search_path = public as $$
begin
  if not public.has_premium(new.owner_id)
     and (select count(*) from public.reisen r where r.owner_id = new.owner_id) >= 1 then
    raise exception 'Die Gratis-Version erlaubt nur eine Reise. Lösche die vorhandene Reise oder löse einen Premium-Code ein.' using errcode = '42501';
  end if;
  return new;
end $$;
drop trigger if exists trip_limit on public.reisen;
create trigger trip_limit before insert on public.reisen for each row execute function public.enforce_trip_limit();

-- ===== Admin =====
-- Code: JAHR-BESTELLNUMMER-MONAT-TAG-NACHNAME (Datum = Ablaufdatum; ohne Ablaufdatum = heutiges Datum)
create or replace function public.admin_create_code(p_order text, p_name text, p_email text default null, p_expires date default null)
returns text language plpgsql security definer set search_path = public as $$
declare d date; o text; n text; c text;
begin
  if not public.is_admin() then raise exception 'Kein Zugriff' using errcode = '42501'; end if;
  if p_expires is not null and p_expires < current_date then raise exception 'Das Ablaufdatum liegt in der Vergangenheit'; end if;
  d := coalesce(p_expires, current_date);
  o := nullif(regexp_replace(coalesce(p_order,''), '[^0-9A-Za-z]', '', 'g'), '');
  if o is null then o := nextval('public.premium_order_seq')::text; end if;
  -- letztes Wort des Namens = Nachname, Umlaute aufgelöst, nur Buchstaben
  n := regexp_replace(btrim(p_name), '^.*\s', '');
  n := upper(regexp_replace(replace(replace(replace(replace(replace(replace(replace(n,'ä','ae'),'ö','oe'),'ü','ue'),'Ä','Ae'),'Ö','Oe'),'Ü','Ue'),'ß','ss'), '[^A-Za-z]', '', 'g'));
  if n = '' then raise exception 'Bitte den Nachnamen angeben'; end if;
  c := extract(year from d)::int || '-' || upper(o) || '-' || lpad(extract(month from d)::int::text, 2, '0') || '-' || lpad(extract(day from d)::int::text, 2, '0') || '-' || n;
  insert into public.premium_codes(code, order_no, customer_name, customer_email, expires_on)
    values (c, upper(o), btrim(p_name), nullif(btrim(coalesce(p_email,'')), ''), p_expires);
  return c;
exception when unique_violation then raise exception 'Diesen Code gibt es schon (gleiche Bestellnummer, Datum und Name)';
end $$;

create or replace function public.admin_list_codes()
returns table (code text, order_no text, customer_name text, customer_email text, expires_on date, created_at timestamptz, revoked boolean, redeemed_at timestamptz, redeemed_by_email text)
language plpgsql security definer set search_path = public, auth as $$
begin
  if not public.is_admin() then raise exception 'Kein Zugriff' using errcode = '42501'; end if;
  return query select p.code, p.order_no, p.customer_name, p.customer_email, p.expires_on, p.created_at, p.revoked, p.redeemed_at, u.email::text
    from public.premium_codes p left join auth.users u on u.id = p.redeemed_by order by p.created_at desc;
end $$;

create or replace function public.admin_revoke_code(p_code text) returns void
language plpgsql security definer set search_path = public as $$
begin
  if not public.is_admin() then raise exception 'Kein Zugriff' using errcode = '42501'; end if;
  update public.premium_codes set revoked = true where code = p_code;
  update public.premium_access set valid_until = now() where code = p_code;   -- Zugriff sofort beenden
end $$;

revoke execute on function public.admin_create_code(text,text,text,date), public.admin_list_codes(), public.admin_revoke_code(text) from public, anon;
grant execute on function public.admin_create_code(text,text,text,date), public.admin_list_codes(), public.admin_revoke_code(text) to authenticated;
