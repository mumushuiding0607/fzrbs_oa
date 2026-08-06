import { Button, Modal, Space, Spin, Statistic, Card, Row, Col, message, Select } from "antd";
import { useEffect, useState } from "react";
import UserAutocomplete from "../budget/common/userAutocomplete";
import { getPendingCount, transferApproval, getappoptions } from "./service";

const TransferApproval: React.FC<{
  visible: boolean;
  onVisibleChange: (v: boolean) => void;
  onSuccess?: () => void;
}> = ({ visible, onVisibleChange, onSuccess }) => {
  const [fromUser, setFromUser] = useState<any>(null);
  const [toUser, setToUser] = useState<any>(null);
  const [agentid, setAgentid] = useState<number | undefined>(undefined);
  const [apps, setApps] = useState<any[]>([]);
  const [pendingCount, setPendingCount] = useState<number | null>(null);
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [countdown, setCountdown] = useState(0);
  const [confirmVisible, setConfirmVisible] = useState(false);

  useEffect(() => {
    if (visible) {
      getappoptions().then((res: any) => {
        setApps(res || []);
      });
    }
  }, [visible]);

  useEffect(() => {
    if (!visible) {
      setFromUser(null);
      setToUser(null);
      setAgentid(undefined);
      setPendingCount(null);
      setLoading(false);
      setSubmitting(false);
      setCountdown(0);
      setConfirmVisible(false);
    }
  }, [visible]);

  useEffect(() => {
    if (countdown > 0) {
      const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
      return () => clearTimeout(timer);
    }
  }, [countdown]);

  const handleQuery = () => {
    if (!fromUser || !fromUser.value) {
      message.error("请选择转出人");
      return;
    }
    setLoading(true);
    getPendingCount({ userid: fromUser.value, agentid })
      .then((res: any) => {
        setPendingCount(res.data ?? 0);
      })
      .catch(() => {
        setPendingCount(0);
      })
      .finally(() => {
        setLoading(false);
      });
  };

  const handleShowConfirm = () => {
    if (!fromUser || !fromUser.value) {
      message.error("请选择转出人");
      return;
    }
    if (!toUser || !toUser.value) {
      message.error("请选择接收人");
      return;
    }
    if (pendingCount === null || pendingCount === 0) {
      message.error("没有可转交的审批");
      return;
    }
    setConfirmVisible(true);
    setCountdown(5);
  };

  const handleConfirm = () => {
    setSubmitting(true);
    transferApproval({ fromUserid: fromUser.value, toUserid: toUser.value, agentid })
      .then((res: any) => {
        if (res.errorMessage) {
          Modal.error({ title: res.errorMessage });
        } else {
          message.success(`成功转交 ${res.data?.successCount ?? 0} 条审批`);
          onSuccess?.();
          setConfirmVisible(false);
          onVisibleChange(false);
        }
      })
      .catch((err: any) => {
        Modal.error({ title: err.message || "转交失败" });
      })
      .finally(() => {
        setSubmitting(false);
      });
  };

  return (
    <>
      <Modal
        title="审批转交"
        style={{ top: 20 }}
        visible={visible}
        onOk={() => onVisibleChange(false)}
        onCancel={() => onVisibleChange(false)}
        footer={null}
        maskClosable={false}
        width={500}
      >
        <div style={{ marginBottom: 16 }}>
          <div style={{ marginBottom: 8, fontWeight: 500 }}>选择应用：</div>
          <Select
            style={{ width: "100%" }}
            placeholder="请选择应用（不选则处理全部）"
            allowClear
            value={agentid}
            onChange={(v) => { setAgentid(v); setPendingCount(null); }}
            options={apps}
          />
        </div>

        <div style={{ marginBottom: 16 }}>
          <div style={{ marginBottom: 8, fontWeight: 500 }}>转出人：</div>
          <UserAutocomplete
            value={fromUser}
            onChange={(user) => { setFromUser(user); setPendingCount(null); }}
            placeholder="请选择转出人"
            multiple={false}
          />
        </div>

        <div style={{ marginBottom: 16 }}>
          <div style={{ marginBottom: 8, fontWeight: 500 }}>接收人：</div>
          <UserAutocomplete
            value={toUser}
            onChange={setToUser}
            placeholder="请选择接收人"
            multiple={false}
          />
        </div>

        <div style={{ marginBottom: 16, textAlign: "center" }}>
          <Button onClick={handleQuery} loading={loading} disabled={!fromUser || !fromUser.value}>
            查询待转交数量
          </Button>
        </div>

        {pendingCount !== null && (
          <Card size="small" style={{ marginBottom: 24 }}>
            <Row gutter={16}>
              <Col span={24}>
                <Statistic
                  title="待转交审批数量"
                  value={pendingCount}
                  valueStyle={{ color: pendingCount > 0 ? "#1890FF" : "#999" }}
                />
              </Col>
            </Row>
            {pendingCount > 0 && fromUser && toUser && (
              <div style={{ marginTop: 8, fontSize: 12, color: "#999" }}>
                将把 {fromUser.label || fromUser.name} 的 {pendingCount} 条申请转交给 {toUser.label || toUser.name}
              </div>
            )}
          </Card>
        )}

        <div style={{ textAlign: "center" }}>
          <Button
            type="primary"
            disabled={!fromUser || !fromUser.value || !toUser || !toUser.value || pendingCount === null || pendingCount === 0}
            onClick={handleShowConfirm}
          >
            确认转交
          </Button>
          <Button onClick={() => onVisibleChange(false)} style={{ marginLeft: 8 }}>取消</Button>
        </div>
      </Modal>

      <Modal
        title="确认转交"
        visible={confirmVisible}
        onCancel={() => setConfirmVisible(false)}
        footer={null}
        closable={false}
        maskClosable={false}
        width={400}
      >
        <div style={{ textAlign: "center", padding: "20px 0" }}>
          <div style={{ marginBottom: 16, fontSize: 16 }}>
            确定要将 <strong>{fromUser?.label || fromUser?.name}</strong> 的 <strong>{pendingCount}</strong> 条待审批转交给 <strong>{toUser?.label || toUser?.name}</strong> 吗？
          </div>
          <div style={{ marginBottom: 16, color: "#ff4d4f", fontSize: 14 }}>
            此操作不可撤回，请谨慎确认！
          </div>
          <Button
            type="primary"
            disabled={countdown > 0}
            onClick={handleConfirm}
            loading={submitting}
          >
            {countdown > 0 ? `${countdown} 秒后可确认` : "确认转交"}
          </Button>
          <Button onClick={() => setConfirmVisible(false)} style={{ marginLeft: 8 }}>取消</Button>
        </div>
      </Modal>
    </>
  );
};

export default TransferApproval;
