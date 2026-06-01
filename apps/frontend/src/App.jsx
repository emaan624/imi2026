import { Navigate, Route, Routes } from 'react-router-dom'
import ProtectedRoute from './components/ProtectedRoute'
import AppLayout from './layouts/AppLayout'
import AdminDashboardPage from './pages/AdminDashboardPage'
import ImeiAnalyticsDashboardPage from './pages/ImeiAnalyticsDashboardPage'
import AdminInstallmentDashboardPage from './pages/AdminInstallmentDashboardPage'
import AdminPTADashboardPage from './pages/AdminPTADashboardPage'
import ImeiBulkUploadPage from './pages/ImeiBulkUploadPage'
import InstallmentDetailsPage from './pages/InstallmentDetailsPage'
import InstallmentPlansPage from './pages/InstallmentPlansPage'
import ImeiOrderTrackingPage from './pages/ImeiOrderTrackingPage'
import ImeiPlaceOrderPage from './pages/ImeiPlaceOrderPage'
import ImeiProviderManagementPage from './pages/ImeiProviderManagementPage'
import ImeiServiceManagementPage from './pages/ImeiServiceManagementPage'
import ImeiServicesPage from './pages/ImeiServicesPage'
import LoginPage from './pages/LoginPage'
import MyInstallmentsPage from './pages/MyInstallmentsPage'
import NotFoundPage from './pages/NotFoundPage'
import PaymentSchedulePage from './pages/PaymentSchedulePage'
import PTACalculatorPage from './pages/PTACalculatorPage'
import PTAOrderFormPage from './pages/PTAOrderFormPage'
import PTAServicesPage from './pages/PTAServicesPage'
import PTAStatusTrackerPage from './pages/PTAStatusTrackerPage'
import UserDashboardPage from './pages/UserDashboardPage'

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />

      <Route
        element={(
          <ProtectedRoute>
            <AppLayout />
          </ProtectedRoute>
        )}
      >
        <Route path="/dashboard" element={<UserDashboardPage />} />
        <Route path="/pta/calculator" element={<PTACalculatorPage />} />
        <Route path="/pta/services" element={<PTAServicesPage />} />
        <Route path="/pta/order" element={<PTAOrderFormPage />} />
        <Route path="/pta/status" element={<PTAStatusTrackerPage />} />
        <Route path="/installments/plans" element={<InstallmentPlansPage />} />
        <Route path="/installments" element={<MyInstallmentsPage />} />
        <Route path="/installments/:contractId" element={<InstallmentDetailsPage />} />
        <Route path="/installments/:contractId/schedule" element={<PaymentSchedulePage />} />
        <Route path="/imei/services" element={<ImeiServicesPage />} />
        <Route path="/imei/order" element={<ImeiPlaceOrderPage />} />
        <Route path="/imei/bulk-upload" element={<ImeiBulkUploadPage />} />
        <Route path="/imei/tracking" element={<ImeiOrderTrackingPage />} />
        <Route
          path="/admin"
          element={(
            <ProtectedRoute adminOnly>
              <AdminDashboardPage />
            </ProtectedRoute>
          )}
        />
        <Route
          path="/admin/pta"
          element={(
            <ProtectedRoute adminOnly>
              <AdminPTADashboardPage />
            </ProtectedRoute>
          )}
        />
        <Route
          path="/admin/installments"
          element={(
            <ProtectedRoute adminOnly>
              <AdminInstallmentDashboardPage />
            </ProtectedRoute>
          )}
        />
        <Route
          path="/admin/imei/providers"
          element={(
            <ProtectedRoute adminOnly>
              <ImeiProviderManagementPage />
            </ProtectedRoute>
          )}
        />
        <Route
          path="/admin/imei/services"
          element={(
            <ProtectedRoute adminOnly>
              <ImeiServiceManagementPage />
            </ProtectedRoute>
          )}
        />
        <Route
          path="/admin/imei/analytics"
          element={(
            <ProtectedRoute adminOnly>
              <ImeiAnalyticsDashboardPage />
            </ProtectedRoute>
          )}
        />
      </Route>

      <Route path="/" element={<Navigate to="/dashboard" replace />} />
      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  )
}
