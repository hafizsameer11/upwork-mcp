"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";

export default function AnalyticsPage() {
  const [data, setData] = useState<any>(null);
  useEffect(() => {
    api("/analytics").then(setData).catch(console.error);
  }, []);

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-3xl font-semibold">Analytics</h1>
        <p className="text-zinc-400">Strategy performance feeds future proposal drafts.</p>
      </header>
      <div className="rounded-xl border border-zinc-800 overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-zinc-900 text-zinc-400 text-left">
            <tr>
              <th className="p-3">Skill</th>
              <th className="p-3">Strategy</th>
              <th className="p-3">Sent</th>
              <th className="p-3">Replies</th>
              <th className="p-3">Hires</th>
            </tr>
          </thead>
          <tbody>
            {(data?.strategies || []).map((s: any) => (
              <tr key={s.id} className="border-t border-zinc-800">
                <td className="p-3">{s.skill}</td>
                <td className="p-3">{s.strategy}</td>
                <td className="p-3">{s.sent}</td>
                <td className="p-3">{s.replies}</td>
                <td className="p-3">{s.hires}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
