import { jsx } from "react/jsx-runtime";
import { Loader2 } from "lucide-react";
import { useStudentAuth } from "@/hooks/useStudentAuth";
import StudentLogin from "./StudentLogin";
import StudentShell from "./StudentShell";
const StudentAuthGate = () => {
  const { status, data, error, login, logout, refresh } = useStudentAuth();
  if (status === "loading") {
    return /* @__PURE__ */ jsx("div", { className: "flex min-h-screen items-center justify-center bg-background", children: /* @__PURE__ */ jsx(Loader2, { className: "h-8 w-8 animate-spin text-primary" }) });
  }
  if (status === "guest" || !data) {
    return /* @__PURE__ */ jsx(StudentLogin, { onSubmit: login, error });
  }
  return /* @__PURE__ */ jsx(StudentShell, { data, onLogout: logout, onRefresh: refresh });
};
export default StudentAuthGate;
