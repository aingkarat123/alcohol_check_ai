/*
  TCS ALCOHOL CHECK AI V4
  SQL Server migration - creates only AC_* objects.
  Review and back up the target database before execution.
  This script does not alter Employee Master or any existing Production table.
*/
SET NOCOUNT ON;
SET XACT_ABORT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.AC_Devices', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.AC_Devices (
            DeviceID           INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_AC_Devices PRIMARY KEY,
            DeviceCode         NVARCHAR(50) NOT NULL,
            DeviceName         NVARCHAR(150) NOT NULL,
            DeviceProfile      NVARCHAR(80) NOT NULL,
            AlcoholUnit        NVARCHAR(20) NOT NULL,
            SerialNumber       NVARCHAR(100) NULL,
            IsActive           BIT NOT NULL CONSTRAINT DF_AC_Devices_IsActive DEFAULT (1),
            CreatedAt          DATETIME2(0) NOT NULL CONSTRAINT DF_AC_Devices_CreatedAt DEFAULT (SYSDATETIME()),
            UpdatedAt          DATETIME2(0) NOT NULL CONSTRAINT DF_AC_Devices_UpdatedAt DEFAULT (SYSDATETIME()),
            CONSTRAINT UQ_AC_Devices_DeviceCode UNIQUE (DeviceCode)
        );
    END;

    IF OBJECT_ID(N'dbo.AC_Checkpoints', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.AC_Checkpoints (
            CheckpointID       INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_AC_Checkpoints PRIMARY KEY,
            CheckpointCode     NVARCHAR(50) NOT NULL,
            CheckpointName     NVARCHAR(150) NOT NULL,
            LocationName       NVARCHAR(150) NULL,
            ProjectName        NVARCHAR(150) NULL,
            IsActive           BIT NOT NULL CONSTRAINT DF_AC_Checkpoints_IsActive DEFAULT (1),
            CreatedAt          DATETIME2(0) NOT NULL CONSTRAINT DF_AC_Checkpoints_CreatedAt DEFAULT (SYSDATETIME()),
            CONSTRAINT UQ_AC_Checkpoints_Code UNIQUE (CheckpointCode)
        );
    END;

    IF OBJECT_ID(N'dbo.AC_Users', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.AC_Users (
            UserID             INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_AC_Users PRIMARY KEY,
            EmployeeCode       NVARCHAR(30) NOT NULL,
            DisplayName        NVARCHAR(200) NOT NULL,
            RoleCode           NVARCHAR(30) NOT NULL,
            PasswordHash       NVARCHAR(255) NULL,
            IsActive           BIT NOT NULL CONSTRAINT DF_AC_Users_IsActive DEFAULT (1),
            LastLoginAt        DATETIME2(0) NULL,
            CreatedAt          DATETIME2(0) NOT NULL CONSTRAINT DF_AC_Users_CreatedAt DEFAULT (SYSDATETIME()),
            UpdatedAt          DATETIME2(0) NOT NULL CONSTRAINT DF_AC_Users_UpdatedAt DEFAULT (SYSDATETIME()),
            CONSTRAINT UQ_AC_Users_EmployeeCode UNIQUE (EmployeeCode),
            CONSTRAINT CK_AC_Users_Role CHECK (RoleCode IN (N'safety_officer', N'safety_manager', N'hr', N'administrator'))
        );
    END;

    IF OBJECT_ID(N'dbo.AC_Settings', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.AC_Settings (
            SettingID          INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_AC_Settings PRIMARY KEY,
            SettingScope       NVARCHAR(30) NOT NULL,
            ScopeReferenceID   INT NULL,
            SettingKey         NVARCHAR(100) NOT NULL,
            SettingValue       NVARCHAR(1000) NULL,
            IsActive           BIT NOT NULL CONSTRAINT DF_AC_Settings_IsActive DEFAULT (1),
            EffectiveFrom      DATETIME2(0) NOT NULL CONSTRAINT DF_AC_Settings_EffectiveFrom DEFAULT (SYSDATETIME()),
            EffectiveTo        DATETIME2(0) NULL,
            UpdatedBy          INT NULL,
            UpdatedAt          DATETIME2(0) NOT NULL CONSTRAINT DF_AC_Settings_UpdatedAt DEFAULT (SYSDATETIME()),
            CONSTRAINT FK_AC_Settings_User FOREIGN KEY (UpdatedBy) REFERENCES dbo.AC_Users(UserID),
            CONSTRAINT UQ_AC_Settings_ScopeKey UNIQUE (SettingScope, ScopeReferenceID, SettingKey)
        );
    END;

    IF OBJECT_ID(N'dbo.AC_TestRoster', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.AC_TestRoster (
            RosterID           BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT PK_AC_TestRoster PRIMARY KEY,
            RosterDate         DATE NOT NULL,
            EmployeeCode       NVARCHAR(30) NOT NULL,
            EmployeeName       NVARCHAR(200) NULL,
            PositionName       NVARCHAR(150) NULL,
            DepartmentName     NVARCHAR(150) NULL,
            ShiftName          NVARCHAR(80) NULL,
            ProjectName        NVARCHAR(150) NULL,
            CheckpointID       INT NULL,
            RequiredStatus     NVARCHAR(20) NOT NULL CONSTRAINT DF_AC_TestRoster_Status DEFAULT (N'REQUIRED'),
            CreatedAt          DATETIME2(0) NOT NULL CONSTRAINT DF_AC_TestRoster_CreatedAt DEFAULT (SYSDATETIME()),
            CONSTRAINT FK_AC_TestRoster_Checkpoint FOREIGN KEY (CheckpointID) REFERENCES dbo.AC_Checkpoints(CheckpointID),
            CONSTRAINT UQ_AC_TestRoster_DateEmployee UNIQUE (RosterDate, EmployeeCode, CheckpointID)
        );
    END;

    IF OBJECT_ID(N'dbo.AC_TestRecords', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.AC_TestRecords (
            TestID                 BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT PK_AC_TestRecords PRIMARY KEY,
            TestNumber             NVARCHAR(40) NOT NULL,
            EmployeeCode           NVARCHAR(30) NOT NULL,
            EmployeeName           NVARCHAR(200) NOT NULL,
            PositionName           NVARCHAR(150) NULL,
            DepartmentName         NVARCHAR(150) NULL,
            DeviceID               INT NOT NULL,
            CheckpointID           INT NOT NULL,
            OriginalOCRValue       DECIMAL(8,3) NULL,
            ConfirmedAlcoholValue  DECIMAL(8,3) NOT NULL,
            AlcoholUnit            NVARCHAR(20) NOT NULL,
            OCRConfidence          DECIMAL(5,4) NULL,
            OCRSource              NVARCHAR(40) NOT NULL,
            ResultStatus           NVARCHAR(20) NOT NULL,
            ThresholdUsed          DECIMAL(8,3) NULL,
            TestDateTime           DATETIME2(0) NOT NULL,
            TestedBy               INT NOT NULL,
            PreviousTestID         BIGINT NULL,
            EditReason             NVARCHAR(500) NULL,
            RowVersion             ROWVERSION NOT NULL,
            CreatedAt              DATETIME2(0) NOT NULL CONSTRAINT DF_AC_TestRecords_CreatedAt DEFAULT (SYSDATETIME()),
            UpdatedAt              DATETIME2(0) NOT NULL CONSTRAINT DF_AC_TestRecords_UpdatedAt DEFAULT (SYSDATETIME()),
            CONSTRAINT UQ_AC_TestRecords_TestNumber UNIQUE (TestNumber),
            CONSTRAINT FK_AC_TestRecords_Device FOREIGN KEY (DeviceID) REFERENCES dbo.AC_Devices(DeviceID),
            CONSTRAINT FK_AC_TestRecords_Checkpoint FOREIGN KEY (CheckpointID) REFERENCES dbo.AC_Checkpoints(CheckpointID),
            CONSTRAINT FK_AC_TestRecords_User FOREIGN KEY (TestedBy) REFERENCES dbo.AC_Users(UserID),
            CONSTRAINT FK_AC_TestRecords_Previous FOREIGN KEY (PreviousTestID) REFERENCES dbo.AC_TestRecords(TestID),
            CONSTRAINT CK_AC_TestRecords_Status CHECK (ResultStatus IN (N'PASS', N'FAIL', N'RETEST', N'REVIEW')),
            CONSTRAINT CK_AC_TestRecords_Value CHECK (ConfirmedAlcoholValue >= 0)
        );
        CREATE INDEX IX_AC_TestRecords_EmployeeDate ON dbo.AC_TestRecords(EmployeeCode, TestDateTime DESC);
        CREATE INDEX IX_AC_TestRecords_StatusDate ON dbo.AC_TestRecords(ResultStatus, TestDateTime DESC);
        CREATE INDEX IX_AC_TestRecords_CheckpointDate ON dbo.AC_TestRecords(CheckpointID, TestDateTime DESC);
    END;

    IF OBJECT_ID(N'dbo.AC_TestImages', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.AC_TestImages (
            ImageID            BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT PK_AC_TestImages PRIMARY KEY,
            TestID             BIGINT NOT NULL,
            ImageType          NVARCHAR(30) NOT NULL,
            StorageKey         NVARCHAR(500) NOT NULL,
            MimeType           NVARCHAR(80) NOT NULL,
            FileSizeBytes      INT NOT NULL,
            SHA256Hash         CHAR(64) NOT NULL,
            CapturedAt         DATETIME2(0) NOT NULL,
            CreatedAt          DATETIME2(0) NOT NULL CONSTRAINT DF_AC_TestImages_CreatedAt DEFAULT (SYSDATETIME()),
            CONSTRAINT FK_AC_TestImages_Test FOREIGN KEY (TestID) REFERENCES dbo.AC_TestRecords(TestID),
            CONSTRAINT CK_AC_TestImages_Type CHECK (ImageType IN (N'EMPLOYEE', N'DEVICE_SCREEN')),
            CONSTRAINT UQ_AC_TestImages_TestType UNIQUE (TestID, ImageType)
        );
    END;

    IF OBJECT_ID(N'dbo.AC_OCRLogs', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.AC_OCRLogs (
            OCRLogID           BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT PK_AC_OCRLogs PRIMARY KEY,
            TestID             BIGINT NULL,
            TestSessionID      NVARCHAR(128) NOT NULL,
            DeviceProfile      NVARCHAR(80) NOT NULL,
            DetectedValue      DECIMAL(8,3) NULL,
            DetectedUnit       NVARCHAR(20) NULL,
            Confidence         DECIMAL(5,4) NULL,
            ProcessingTimeMs   INT NULL,
            WasSuccessful      BIT NOT NULL,
            NeedsReview        BIT NOT NULL,
            ErrorCode          NVARCHAR(80) NULL,
            ErrorMessage       NVARCHAR(1000) NULL,
            CreatedAt          DATETIME2(0) NOT NULL CONSTRAINT DF_AC_OCRLogs_CreatedAt DEFAULT (SYSDATETIME()),
            CONSTRAINT FK_AC_OCRLogs_Test FOREIGN KEY (TestID) REFERENCES dbo.AC_TestRecords(TestID)
        );
        CREATE INDEX IX_AC_OCRLogs_CreatedAt ON dbo.AC_OCRLogs(CreatedAt DESC);
    END;

    IF OBJECT_ID(N'dbo.AC_AuditLogs', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.AC_AuditLogs (
            AuditLogID         BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT PK_AC_AuditLogs PRIMARY KEY,
            UserID             INT NULL,
            ActionCode         NVARCHAR(100) NOT NULL,
            EntityType        NVARCHAR(80) NULL,
            EntityID          NVARCHAR(80) NULL,
            DetailJson        NVARCHAR(MAX) NULL,
            IpAddress         NVARCHAR(64) NULL,
            UserAgent         NVARCHAR(500) NULL,
            CreatedAt         DATETIME2(0) NOT NULL CONSTRAINT DF_AC_AuditLogs_CreatedAt DEFAULT (SYSDATETIME()),
            CONSTRAINT FK_AC_AuditLogs_User FOREIGN KEY (UserID) REFERENCES dbo.AC_Users(UserID)
        );
        CREATE INDEX IX_AC_AuditLogs_Entity ON dbo.AC_AuditLogs(EntityType, EntityID, CreatedAt DESC);
    END;

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
    RAISERROR(@ErrorMessage, 16, 1);
END CATCH;
