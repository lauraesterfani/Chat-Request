"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { User, LogOut, LayoutDashboard, MessageSquare, Bell } from "lucide-react";
import { useAuth } from "../app/context/AuthContext";

export default function Navbar() {
  const pathname = usePathname();
  const { user, logout } = useAuth();

  const navLinks = [
    { name: "Início", href: "/me", icon: <MessageSquare size={18} /> },
    { name: "Meus Pedidos", href: "/dashboard", icon: <LayoutDashboard size={18} /> },
  ];

  return (
    <nav className="sticky top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-gray-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16 items-center">
          
          {/* Logo e Mascot */}
          <Link href="/me" className="flex items-center gap-3 group">
            <img 
              src="/mascote_simples.png" 
              alt="Mascote" 
              className="h-10 w-auto group-hover:scale-110 transition-transform" 
            />
            <span className="text-xl font-black text-slate-800 tracking-tighter">
              Chat Request
            </span>
          </Link>

          {/* Links Centrais (Desktop) */}
          <div className="hidden md:flex items-center gap-8">
            {navLinks.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className={`flex items-center gap-2 text-sm font-bold transition-colors ${
                  pathname === link.href ? "text-[#108542]" : "text-slate-500 hover:text-[#108542]"
                }`}
              >
                {link.icon}
                {link.name}
              </Link>
            ))}
          </div>

          {/* Ações da Direita (Notificação e Perfil) */}
          <div className="flex items-center gap-4">
            <button className="p-2 text-slate-400 hover:text-[#108542] transition-colors relative">
              <Bell size={20} />
              <span className="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
            </button>
            
            <div className="h-8 w-[1px] bg-gray-100 mx-2"></div>

            <div className="flex items-center gap-3 pl-2">
              <div className="text-right hidden sm:block">
                <p className="text-xs font-black text-slate-800 leading-none">
                  {user?.name?.split(' ')[0] || "Usuário"}
                </p>
                <p className="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Estudante</p>
              </div>
              <button 
                onClick={logout}
                className="w-10 h-10 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center text-slate-400 hover:bg-red-50 hover:text-red-500 hover:border-red-100 transition-all shadow-sm"
              >
                <LogOut size={18} />
              </button>
            </div>
          </div>

        </div>
      </div>
    </nav>
  );
}