"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";

const nav = [
  { href: "/", label: "Dashboard" },
  { href: "/jobs", label: "Jobs" },
  { href: "/proposals", label: "Proposals" },
  { href: "/messages", label: "Messages" },
  { href: "/portfolio", label: "Portfolio" },
  { href: "/search-profiles", label: "Search Profiles" },
  { href: "/analytics", label: "Analytics" },
  { href: "/accounts", label: "Accounts" },
  { href: "/scheduler", label: "Scheduler" },
  { href: "/settings", label: "Settings" },
];

export function Shell({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();

  if (pathname === "/login") {
    return <>{children}</>;
  }

  return (
    <div className="min-h-screen bg-zinc-950 text-zinc-100 flex">
      <aside className="w-60 border-r border-zinc-800 p-4 flex flex-col gap-1">
        <div className="mb-6">
          <div className="text-xs uppercase tracking-[0.2em] text-emerald-400">HMS Tech</div>
          <div className="text-lg font-semibold">Upwork Intelligence</div>
        </div>
        {nav.map((item) => {
          const active = pathname === item.href || (item.href !== "/" && pathname.startsWith(item.href));
          return (
            <Link
              key={item.href}
              href={item.href}
              className={`rounded-md px-3 py-2 text-sm ${active ? "bg-emerald-500/15 text-emerald-300" : "text-zinc-400 hover:bg-zinc-900 hover:text-zinc-100"}`}
            >
              {item.label}
            </Link>
          );
        })}
        <button
          className="mt-auto text-left text-sm text-zinc-500 hover:text-zinc-300 px-3 py-2"
          onClick={() => {
            localStorage.removeItem("token");
            router.push("/login");
          }}
        >
          Sign out
        </button>
      </aside>
      <main className="flex-1 p-8 overflow-auto">{children}</main>
    </div>
  );
}
