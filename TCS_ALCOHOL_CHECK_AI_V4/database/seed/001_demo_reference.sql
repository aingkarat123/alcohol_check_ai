/*
  Optional reference data for a separate TEST database only.
  Do not execute against Production without DBA approval.
*/
SET NOCOUNT ON;

IF NOT EXISTS (SELECT 1 FROM dbo.AC_Devices WHERE DeviceCode = N'DEMO-LCD-01')
    INSERT dbo.AC_Devices (DeviceCode, DeviceName, DeviceProfile, AlcoholUnit, SerialNumber)
    VALUES (N'DEMO-LCD-01', N'Generic LCD Demo Device', N'generic_lcd', N'mg/L', N'DEMO-ONLY');

IF NOT EXISTS (SELECT 1 FROM dbo.AC_Checkpoints WHERE CheckpointCode = N'MAIN-GATE')
    INSERT dbo.AC_Checkpoints (CheckpointCode, CheckpointName, LocationName)
    VALUES (N'MAIN-GATE', N'ประตูหลัก', N'TCS Demo Site');

IF NOT EXISTS (SELECT 1 FROM dbo.AC_Users WHERE EmployeeCode = N'SAFE001')
    INSERT dbo.AC_Users (EmployeeCode, DisplayName, RoleCode)
    VALUES (N'SAFE001', N'เจ้าหน้าที่ Safety (Demo)', N'safety_manager');
