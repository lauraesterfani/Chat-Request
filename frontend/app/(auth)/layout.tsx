export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  // Retorna o children sem divs container extras que atrapalham o full-screen
  return <>{children}</>;
}