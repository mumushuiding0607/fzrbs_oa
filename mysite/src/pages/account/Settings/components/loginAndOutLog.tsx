import AdminLoginLogList from '@/pages/admin/components/AdminLoginLogList';
import { useModel } from 'umi';

const LoginAndOutLogView: React.FC = () => {
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;

  return (
    <>
      <AdminLoginLogList showSearchForm={false} username={currentUser?.username} />
    </>
  );
};

export default LoginAndOutLogView;
